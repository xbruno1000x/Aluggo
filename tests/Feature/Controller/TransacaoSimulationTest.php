<?php

use App\Models\Imovel;
use App\Models\Propriedade;
use App\Models\Proprietario;
use App\Models\Aluguel;
use App\Models\Locatario;
use App\Models\Pagamento;
use App\Models\Obra;
use App\Models\Taxa;
use Carbon\Carbon;

beforeEach(function () {
    $this->owner = Proprietario::factory()->create();
    $this->actingAs($this->owner, 'proprietario');

    $this->propriedade = Propriedade::factory()->create([
        'proprietario_id' => $this->owner->id,
    ]);

    $this->imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'status' => 'disponivel',
        'valor_compra' => 200000,
        'data_aquisicao' => Carbon::now()->subYears(2)->toDateString(),
    ]);
});

test('showSimulation exibe formulário de simulação de venda', function () {
    $response = $this->get(route('imoveis.simular-venda', $this->imovel));
    
    $response->assertStatus(200);
    $response->assertViewIs('transacoes.simulate');
    $response->assertViewHas('imovel');
    $response->assertViewHas('valorSugerido');
    $response->assertSee($this->imovel->nome);
});

test('showSimulation sugere valor baseado no valor de compra', function () {
    $response = $this->get(route('imoveis.simular-venda', $this->imovel));
    
    $response->assertViewHas('valorSugerido', 200000);
});

test('showSimulation sugere valor baseado em aluguel quando disponível', function () {
    $locatario = Locatario::factory()->create();
    Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $locatario->id,
        'valor_mensal' => 2000,
        'data_inicio' => Carbon::now()->subYear()->toDateString(),
    ]);

    $response = $this->get(route('imoveis.simular-venda', $this->imovel));
    
    // Deve sugerir 100x o valor do aluguel (200.000)
    $response->assertViewHas('valorSugerido', 200000);
});

test('showSimulation aborta quando imóvel não pertence ao proprietário', function () {
    $outroProprietario = Proprietario::factory()->create();
    $outraPropriedade = Propriedade::factory()->create(['proprietario_id' => $outroProprietario->id]);
    $outroImovel = Imovel::factory()->create(['propriedade_id' => $outraPropriedade->id]);

    $response = $this->get(route('imoveis.simular-venda', $outroImovel));
    
    $response->assertStatus(403);
});

test('simulate processa simulação e retorna métricas', function () {
    $response = $this->post(route('imoveis.simular-venda.post', $this->imovel), [
        'valor_venda' => 300000,
        'data_venda' => Carbon::now()->toDateString(),
    ]);
    
    $response->assertStatus(200);
    $response->assertViewIs('transacoes.simulate-result');
    $response->assertViewHas('imovel');
    $response->assertViewHas('simulacao');
    $response->assertViewHas('lucro', 100000.0);
    $response->assertViewHas('porcentagem', 50.0);
});

test('simulate valida campos obrigatórios', function () {
    $response = $this->post(route('imoveis.simular-venda.post', $this->imovel), []);
    
    $response->assertSessionHasErrors(['valor_venda', 'data_venda']);
});

test('simulate valida valor mínimo de venda', function () {
    $response = $this->post(route('imoveis.simular-venda.post', $this->imovel), [
        'valor_venda' => 0,
        'data_venda' => Carbon::now()->toDateString(),
    ]);
    
    $response->assertSessionHasErrors('valor_venda');
});

test('simulate calcula receitas de aluguel no período', function () {
    $locatario = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $locatario->id,
        'valor_mensal' => 2000,
        'data_inicio' => Carbon::now()->subYear()->toDateString(),
    ]);

    // Criar alguns pagamentos
    \App\Models\Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => Carbon::now()->subMonths(2)->startOfMonth()->toDateString(),
        'valor_devido' => 2000,
        'valor_recebido' => 2000,
        'status' => 'paid',
    ]);

    $response = $this->post(route('imoveis.simular-venda.post', $this->imovel), [
        'valor_venda' => 300000,
        'data_venda' => Carbon::now()->toDateString(),
    ]);
    
    $response->assertStatus(200);
    $response->assertViewHas('rentalIncome');
    expect($response->viewData('rentalIncome'))->toBeGreaterThan(0);
});

test('simulate calcula despesas com obras no período', function () {
    Obra::factory()->create([
        'imovel_id' => $this->imovel->id,
        'descricao' => 'Reforma',
        'valor' => 10000,
        'data_inicio' => Carbon::now()->subMonths(6)->toDateString(),
        'data_fim' => Carbon::now()->subMonths(5)->toDateString(),
    ]);

    $response = $this->post(route('imoveis.simular-venda.post', $this->imovel), [
        'valor_venda' => 300000,
        'data_venda' => Carbon::now()->toDateString(),
    ]);
    
    $response->assertStatus(200);
    $response->assertViewHas('obraExpenses', 10000.0);
});

test('simulate calcula despesas com taxas no período', function () {
    Taxa::factory()->create([
        'imovel_id' => $this->imovel->id,
        'tipo' => 'iptu',
        'valor' => 1500,
        'data_pagamento' => Carbon::now()->subMonths(3)->toDateString(),
        'pagador' => 'proprietario',
    ]);

    $response = $this->post(route('imoveis.simular-venda.post', $this->imovel), [
        'valor_venda' => 300000,
        'data_venda' => Carbon::now()->toDateString(),
    ]);
    
    $response->assertStatus(200);
    $response->assertViewHas('taxExpenses', 1500.0);
});

test('simulate calcula lucro ajustado corretamente', function () {
    // Criar aluguel e pagamento
    $locatario = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $locatario->id,
        'valor_mensal' => 2000,
        'data_inicio' => Carbon::now()->subYear()->toDateString(),
    ]);

    \App\Models\Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => Carbon::now()->subMonths(2)->startOfMonth()->toDateString(),
        'valor_devido' => 2000,
        'valor_recebido' => 2000,
        'status' => 'paid',
    ]);

    // Criar obra
    Obra::factory()->create([
        'imovel_id' => $this->imovel->id,
        'valor' => 5000,
        'data_inicio' => Carbon::now()->subMonths(6)->toDateString(),
    ]);

    // Criar taxa
    Taxa::factory()->create([
        'imovel_id' => $this->imovel->id,
        'tipo' => 'iptu',
        'valor' => 1000,
        'data_pagamento' => Carbon::now()->subMonths(3)->toDateString(),
        'pagador' => 'proprietario',
    ]);

    $response = $this->post(route('imoveis.simular-venda.post', $this->imovel), [
        'valor_venda' => 300000,
        'data_venda' => Carbon::now()->toDateString(),
    ]);
    
    // Lucro ajustado = (300000 - 200000) + 2000 - 5000 - 1000 = 96000
    $response->assertStatus(200);
    $response->assertViewHas('adjustedProfit', 96000.0);
});

test('simulate não salva dados no banco', function () {
    $transacoesAntes = \App\Models\Transacao::count();

    $this->post(route('imoveis.simular-venda.post', $this->imovel), [
        'valor_venda' => 300000,
        'data_venda' => Carbon::now()->toDateString(),
    ]);
    
    $transacoesDepois = \App\Models\Transacao::count();
    expect($transacoesDepois)->toBe($transacoesAntes);
});

test('simulate exibe texto de período quando tem data de aquisição', function () {
    $response = $this->post(route('imoveis.simular-venda.post', $this->imovel), [
        'valor_venda' => 300000,
        'data_venda' => Carbon::now()->toDateString(),
    ]);
    
    $response->assertStatus(200);
    $response->assertViewHas('periodText');
    expect($response->viewData('periodText'))->toContain('dias');
});
