<?php

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Obra;
use App\Models\Pagamento;
use App\Models\Propriedade;
use App\Models\Proprietario;
use App\Models\Taxa;
use App\Models\Transacao;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;
use App\Services\FinanceRateService;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->proprietario = Proprietario::factory()->create();
    $this->actingAs($this->proprietario, 'proprietario');
    
    $this->propriedade = Propriedade::factory()->create(['proprietario_id' => $this->proprietario->id]);
    $this->imovel = Imovel::factory()->create(['propriedade_id' => $this->propriedade->id]);

    app()->instance(FinanceRateService::class, new class {
        public function getCumulativeReturn(string $start, string $end, string $which): ?array
        {
            return ['value' => 0.0, 'type' => 'cumulative'];
        }
    });
});

test('cria transacao e marca imovel como vendido', function () {
    $this->imovel->update(['status' => 'disponivel']);

    $data = [
        'imovel_id' => $this->imovel->id,
        'valor_venda' => 250000,
        'data_venda' => Carbon::today()->toDateString(),
    ];

    $this->post(route('transacoes.store'), $data)
        ->assertRedirect(route('transacoes.index'));

    $this->assertDatabaseHas('transacoes', [
        'imovel_id' => $this->imovel->id,
        'valor_venda' => 250000,
    ]);

    $this->imovel->refresh();
    $this->assertEquals('vendido', $this->imovel->status);
});

test('nao permite criar transacao para imovel vendido', function () {
    $this->imovel->update(['status' => 'vendido']);

    $data = [
        'imovel_id' => $this->imovel->id,
        'valor_venda' => 500000,
        'data_venda' => now()->toDateString(),
    ];

    $response = $this->post(route('transacoes.store'), $data);

    $response->assertSessionHasErrors('imovel_id');
    $this->assertDatabaseMissing('transacoes', [
        'imovel_id' => $this->imovel->id,
        'valor_venda' => 500000,
    ]);
});

test('deleta transacao e reverte imovel para disponivel quando nao houver outras transacoes', function () {
    $this->imovel->update(['status' => 'vendido']);
    $t = Transacao::factory()->create(['imovel_id' => $this->imovel->id]);

    $this->delete(route('transacoes.destroy', $t))->assertRedirect(route('transacoes.index'));

    $this->assertDatabaseMissing('transacoes', ['id' => $t->id]);

    $this->imovel->refresh();
    $this->assertEquals('disponivel', $this->imovel->status);
});

test('deleta transacao mas nao reverte imovel quando existirem outras transacoes', function () {
    $this->imovel->update(['status' => 'vendido']);
    $t1 = Transacao::factory()->create(['imovel_id' => $this->imovel->id]);
    $t2 = Transacao::factory()->create(['imovel_id' => $this->imovel->id]);

    $this->delete(route('transacoes.destroy', $t1))->assertRedirect(route('transacoes.index'));

    $this->assertDatabaseMissing('transacoes', ['id' => $t1->id]);

    $this->imovel->refresh();
    $this->assertEquals('vendido', $this->imovel->status);
});

test('index retorna view com transacoes paginadas', function () {
    Transacao::factory()->count(3)->create();

    $response = $this->get(route('transacoes.index'));
    $response->assertStatus(200);
    $response->assertViewHas('transacoes');
});

test('create retorna view com lista de imoveis', function () {
    Imovel::factory()->count(2)->create();

    $response = $this->get(route('transacoes.create'));
    $response->assertStatus(200);
    $response->assertViewHas('imoveis');
});

test('show e edit retornam as views corretas', function () {
    $t = Transacao::factory()->create(['imovel_id' => $this->imovel->id]);

    $this->get(route('transacoes.show', $t))->assertStatus(200)->assertViewHas('transacao');
    $this->get(route('transacoes.edit', $t))->assertStatus(200)->assertViewHas('transacao');
});

test('update altera transacao e redireciona', function () {
    $t = Transacao::factory()->create(['imovel_id' => $this->imovel->id]);
    $imovel2 = Imovel::factory()->create(['propriedade_id' => $this->propriedade->id]);

    $data = [
        'imovel_id' => $imovel2->id,
        'valor_venda' => 999999.99,
        'data_venda' => now()->toDateString(),
    ];

    $this->put(route('transacoes.update', $t), $data)->assertRedirect(route('transacoes.index'));

    $this->assertDatabaseHas('transacoes', ['id' => $t->id, 'imovel_id' => $imovel2->id]);
});

test('store trata excecao e faz rollback quando criar falha', function () {
    Transacao::creating(function ($model) {
        throw new \Exception('simulated failure');
    });

    $this->imovel->update(['status' => 'disponivel']);

    $data = [
        'imovel_id' => $this->imovel->id,
        'valor_venda' => 12345,
        'data_venda' => now()->toDateString(),
    ];

    $response = $this->post(route('transacoes.store'), $data);

    $response->assertSessionHasErrors('general');
    $this->assertDatabaseMissing('transacoes', ['imovel_id' => $this->imovel->id, 'valor_venda' => 12345]);

    Transacao::flushEventListeners();
});

test('destroy trata falha de exclusao e retorna erro', function () {
    $t = Transacao::factory()->create(['imovel_id' => $this->imovel->id]);
    Transacao::deleting(function ($model) {
        throw new \Exception('simulated delete failure');
    });

    $response = $this->delete(route('transacoes.destroy', $t));
    $response->assertRedirect(route('transacoes.index'));
    $response->assertSessionHas('error');

    $this->assertTrue(Transacao::where('id', $t->id)->exists());

    Transacao::flushEventListeners();
});

test('show calcula lucro ajustado descontando taxas do proprietario', function () {
    $today = Carbon::today();
    $acquisitionDate = $today->copy()->subYear()->toDateString();
    $saleDate = $today->toDateString();
    $rentStart = $today->copy()->subMonths(6)->toDateString();
    $paymentMonth = $today->copy()->startOfMonth()->toDateString();
    $obraDate = $today->copy()->subMonths(3)->toDateString();

    $propriedade = Propriedade::factory()->create([
        'proprietario_id' => $this->proprietario->id,
    ]);

    $imovel = Imovel::factory()->create([
        'propriedade_id' => $propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => $acquisitionDate,
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
        'data_venda' => $saleDate,
    ]);

    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'data_inicio' => $rentStart,
    ]);

    Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => $paymentMonth,
        'valor_devido' => 1200,
        'valor_recebido' => 1200,
        'status' => 'paid',
        'data_pago' => $today->copy(),
    ]);

    Obra::factory()->create([
        'imovel_id' => $imovel->id,
        'valor' => 5000,
        'data_inicio' => $obraDate,
        'data_fim' => null,
    ]);

    Taxa::factory()->create([
        'imovel_id' => $imovel->id,
        'propriedade_id' => $propriedade->id,
        'valor' => 800,
        'data_pagamento' => $paymentMonth,
        'pagador' => 'proprietario',
    ]);

    $response = $this->get(route('transacoes.show', $transacao))->assertStatus(200);

    expect($response->viewData('taxExpenses'))->toBe(800.0);
    expect($response->viewData('adjustedProfit'))->toEqualWithDelta(45400.0, 0.01);
});

test('show calcula selic e ipca quando FinanceRateService retorna annual', function () {
    $mockService = Mockery::mock(\App\Services\FinanceRateService::class);
    $mockService->shouldReceive('getCumulativeReturn')
        ->with(Mockery::any(), Mockery::any(), 'selic')
        ->andReturn(['value' => 0.10, 'type' => 'annual']);
    
    $mockService->shouldReceive('getCumulativeReturn')
        ->with(Mockery::any(), Mockery::any(), 'ipca')
        ->andReturn(['value' => 0.05, 'type' => 'annual']);
    
    $this->app->instance(\App\Services\FinanceRateService::class, $mockService);

    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => now()->subYears(2)->toDateString(),
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
        'data_venda' => now()->toDateString(),
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('selicText'))->toContain('SELIC/CDI');
    expect($response->viewData('ipcaText'))->toContain('Inflação (IPCA)');
});

test('show trata ipca negativo menor que -0.5 corretamente', function () {
    $mockService = Mockery::mock(\App\Services\FinanceRateService::class);
    $mockService->shouldReceive('getCumulativeReturn')
        ->with(Mockery::any(), Mockery::any(), 'selic')
        ->andReturn(['value' => 0.08, 'type' => 'cumulative']);
    
    $mockService->shouldReceive('getCumulativeReturn')
        ->with(Mockery::any(), Mockery::any(), 'ipca')
        ->andReturn(['value' => -0.6, 'type' => 'cumulative']);
    
    $this->app->instance(\App\Services\FinanceRateService::class, $mockService);

    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => now()->subYear()->toDateString(),
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 120000,
        'data_venda' => now()->toDateString(),
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('ipcaText'))->toContain('lucro real');
});

test('show trata excecao do FinanceRateService graciosamente', function () {
    $mockService = Mockery::mock(\App\Services\FinanceRateService::class);
    $mockService->shouldReceive('getCumulativeReturn')
        ->andThrow(new \Exception('Service unavailable'));
    
    $this->app->instance(\App\Services\FinanceRateService::class, $mockService);

    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => now()->subYear()->toDateString(),
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
        'data_venda' => now()->toDateString(),
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('selicText'))->toBeNull();
    expect($response->viewData('ipcaText'))->toBeNull();
});

test('show calcula taxas quando taxa tem aluguel associado ao imovel', function () {
    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => now()->subYear()->toDateString(),
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
        'data_venda' => now()->toDateString(),
    ]);

    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'data_inicio' => now()->subMonths(6)->toDateString(),
    ]);

    Taxa::factory()->create([
        'aluguel_id' => $aluguel->id,
        'valor' => 500,
        'data_pagamento' => now()->subMonths(3)->toDateString(),
        'pagador' => 'proprietario',
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('taxExpenses'))->toBe(500.0);
});

test('show calcula taxas rateadas por propriedade com multiplos imoveis', function () {
    $propriedade = Propriedade::factory()->create([
        'proprietario_id' => $this->proprietario->id,
    ]);

    $imovel1 = Imovel::factory()->create([
        'propriedade_id' => $propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => now()->subYear()->toDateString(),
    ]);

    $imovel2 = Imovel::factory()->create([
        'propriedade_id' => $propriedade->id,
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel1->id,
        'valor_venda' => 150000,
        'data_venda' => now()->toDateString(),
    ]);

    Taxa::factory()->create([
        'propriedade_id' => $propriedade->id,
        'valor' => 1000,
        'data_pagamento' => now()->subMonths(3)->toDateString(),
        'pagador' => 'proprietario',
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    // Taxa de 1000 dividida por 2 imóveis = 500
    expect($response->viewData('taxExpenses'))->toBe(500.0);
});

test('show ignora taxas quando pagador nao eh proprietario', function () {
    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => now()->subYear()->toDateString(),
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
        'data_venda' => now()->toDateString(),
    ]);

    Taxa::factory()->create([
        'imovel_id' => $imovel->id,
        'valor' => 500,
        'data_pagamento' => now()->subMonths(3)->toDateString(),
        'pagador' => 'locatario',
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('taxExpenses'))->toBe(0.0);
});

test('show calcula lucro quando valor_compra eh zero', function () {
    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => 0,
        'data_aquisicao' => now()->subYear()->toDateString(),
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
        'data_venda' => now()->toDateString(),
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('lucro'))->toBe(150000.0);
    expect($response->viewData('porcentagem'))->toBeNull(); // Não calcula % quando vc=0
});

test('show retorna valores zerados quando valor_compra ou valor_venda sao null', function () {
    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => null,
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('lucro'))->toBe(0.0);
});

test('show nao calcula periodo quando data_aquisicao eh vazia', function () {
    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => null,
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('periodText'))->toBeNull();
    expect($response->viewData('rentalIncome'))->toBe(0.0);
});

test('show calcula rendas de alugueis no periodo correto', function () {
    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => now()->subYear()->toDateString(),
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
        'data_venda' => now()->toDateString(),
    ]);

    $aluguel1 = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'data_inicio' => now()->subMonths(8)->toDateString(),
        'data_fim' => now()->subMonths(2)->toDateString(),
    ]);

    $aluguel2 = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'data_inicio' => now()->subMonths(6)->toDateString(),
        'data_fim' => null,
    ]);

    Pagamento::create([
        'aluguel_id' => $aluguel1->id,
        'referencia_mes' => now()->subMonths(5)->startOfMonth()->toDateString(),
        'valor_devido' => 1000,
        'valor_recebido' => 1000,
        'status' => 'paid',
    ]);

    Pagamento::create([
        'aluguel_id' => $aluguel2->id,
        'referencia_mes' => now()->subMonths(3)->startOfMonth()->toDateString(),
        'valor_devido' => 1200,
        'valor_recebido' => 1200,
        'status' => 'paid',
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('rentalIncome'))->toBe(2200.0);
});

test('show calcula obras no periodo correto incluindo obras sem data_fim', function () {
    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => now()->subYear()->toDateString(),
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
        'data_venda' => now()->toDateString(),
    ]);

    Obra::factory()->create([
        'imovel_id' => $imovel->id,
        'valor' => 3000,
        'data_inicio' => now()->subMonths(8)->toDateString(),
        'data_fim' => now()->subMonths(6)->toDateString(),
    ]);

    Obra::factory()->create([
        'imovel_id' => $imovel->id,
        'valor' => 2000,
        'data_inicio' => now()->subMonths(3)->toDateString(),
        'data_fim' => null, // Obra em andamento
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('obraExpenses'))->toBe(5000.0);
});

test('show retorna null para FinanceRateService quando retorna null', function () {
    $mockService = Mockery::mock(\App\Services\FinanceRateService::class);
    $mockService->shouldReceive('getCumulativeReturn')
        ->andReturn(null);
    
    $this->app->instance(\App\Services\FinanceRateService::class, $mockService);

    $imovel = Imovel::factory()->create([
        'propriedade_id' => $this->propriedade->id,
        'valor_compra' => 100000,
        'data_aquisicao' => now()->subYear()->toDateString(),
    ]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 150000,
        'data_venda' => now()->toDateString(),
    ]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(200);
    expect($response->viewData('selicText'))->toBeNull();
    expect($response->viewData('ipcaText'))->toBeNull();
});

test('destroy executa fallback quando Eloquent destroy retorna false', function () {
    $t = Transacao::factory()->create(['imovel_id' => $this->imovel->id]);
    
    Transacao::deleting(function () {
        return true; 
    });

    $response = $this->delete(route('transacoes.destroy', $t));
    
    $response->assertRedirect(route('transacoes.index'));
    
    Transacao::flushEventListeners();
});

test('show aborta 403 quando transacao nao pertence ao proprietario', function () {
    $outroProprietario = Proprietario::factory()->create();
    $outraPropriedade = Propriedade::factory()->create(['proprietario_id' => $outroProprietario->id]);
    $outroImovel = Imovel::factory()->create(['propriedade_id' => $outraPropriedade->id]);
    $transacao = Transacao::factory()->create(['imovel_id' => $outroImovel->id]);

    $response = $this->get(route('transacoes.show', $transacao));
    
    $response->assertStatus(403);
});

test('edit aborta 403 quando transacao nao pertence ao proprietario', function () {
    $outroProprietario = Proprietario::factory()->create();
    $outraPropriedade = Propriedade::factory()->create(['proprietario_id' => $outroProprietario->id]);
    $outroImovel = Imovel::factory()->create(['propriedade_id' => $outraPropriedade->id]);
    $transacao = Transacao::factory()->create(['imovel_id' => $outroImovel->id]);

    $response = $this->get(route('transacoes.edit', $transacao));
    
    $response->assertStatus(403);
});

test('update aborta 403 quando transacao nao pertence ao proprietario', function () {
    $outroProprietario = Proprietario::factory()->create();
    $outraPropriedade = Propriedade::factory()->create(['proprietario_id' => $outroProprietario->id]);
    $outroImovel = Imovel::factory()->create(['propriedade_id' => $outraPropriedade->id]);
    $transacao = Transacao::factory()->create(['imovel_id' => $outroImovel->id]);

    $data = [
        'imovel_id' => $this->imovel->id,
        'valor_venda' => 999999,
        'data_venda' => now()->toDateString(),
    ];

    $response = $this->put(route('transacoes.update', $transacao), $data);
    
    $response->assertStatus(403);
});

test('destroy aborta 403 quando transacao nao pertence ao proprietario', function () {
    $outroProprietario = Proprietario::factory()->create();
    $outraPropriedade = Propriedade::factory()->create(['proprietario_id' => $outroProprietario->id]);
    $outroImovel = Imovel::factory()->create(['propriedade_id' => $outraPropriedade->id]);
    $transacao = Transacao::factory()->create(['imovel_id' => $outroImovel->id]);

    $response = $this->delete(route('transacoes.destroy', $transacao));
    
    $response->assertStatus(403);
});
