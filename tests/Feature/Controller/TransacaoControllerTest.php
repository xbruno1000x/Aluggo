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

    app()->instance(FinanceRateService::class, new class {
        public function getCumulativeReturn(string $start, string $end, string $which): ?array
        {
            return ['value' => 0.0, 'type' => 'cumulative'];
        }
    });
});

test('cria transacao e marca imovel como vendido', function () {
    $imovel = Imovel::factory()->create(['status' => 'disponivel']);

    $data = [
        'imovel_id' => $imovel->id,
        'valor_venda' => 250000,
        'data_venda' => Carbon::today()->toDateString(),
    ];

    $this->post(route('transacoes.store'), $data)
        ->assertRedirect(route('transacoes.index'));

    $this->assertDatabaseHas('transacoes', [
        'imovel_id' => $imovel->id,
        'valor_venda' => 250000,
    ]);

    $imovel->refresh();
    $this->assertEquals('vendido', $imovel->status);
});

test('nao permite criar transacao para imovel vendido', function () {
    $imovel = Imovel::factory()->create(['status' => 'vendido']);

    $data = [
        'imovel_id' => $imovel->id,
        'valor_venda' => 500000,
        'data_venda' => now()->toDateString(),
    ];

    $response = $this->post(route('transacoes.store'), $data);

    $response->assertSessionHasErrors('imovel_id');
    $this->assertDatabaseMissing('transacoes', [
        'imovel_id' => $imovel->id,
        'valor_venda' => 500000,
    ]);
});

test('deleta transacao e reverte imovel para disponivel quando nao houver outras transacoes', function () {
    $imovel = Imovel::factory()->create(['status' => 'vendido']);
    $t = Transacao::factory()->create(['imovel_id' => $imovel->id]);

    $this->delete(route('transacoes.destroy', $t))->assertRedirect(route('transacoes.index'));

    $this->assertDatabaseMissing('transacoes', ['id' => $t->id]);

    $imovel->refresh();
    $this->assertEquals('disponivel', $imovel->status);
});

test('deleta transacao mas nao reverte imovel quando existirem outras transacoes', function () {
    $imovel = Imovel::factory()->create(['status' => 'vendido']);
    $t1 = Transacao::factory()->create(['imovel_id' => $imovel->id]);
    $t2 = Transacao::factory()->create(['imovel_id' => $imovel->id]);

    $this->delete(route('transacoes.destroy', $t1))->assertRedirect(route('transacoes.index'));

    $this->assertDatabaseMissing('transacoes', ['id' => $t1->id]);

    $imovel->refresh();
    $this->assertEquals('vendido', $imovel->status);
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
    $t = Transacao::factory()->create();

    $this->get(route('transacoes.show', $t))->assertStatus(200)->assertViewHas('transacao');
    $this->get(route('transacoes.edit', $t))->assertStatus(200)->assertViewHas('transacao');
});

test('update altera transacao e redireciona', function () {
    $t = Transacao::factory()->create();
    $imovel2 = Imovel::factory()->create();

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

    $imovel = Imovel::factory()->create(['status' => 'disponivel']);

    $data = [
        'imovel_id' => $imovel->id,
        'valor_venda' => 12345,
        'data_venda' => now()->toDateString(),
    ];

    $response = $this->post(route('transacoes.store'), $data);

    $response->assertSessionHasErrors('general');
    $this->assertDatabaseMissing('transacoes', ['imovel_id' => $imovel->id, 'valor_venda' => 12345]);

    Transacao::flushEventListeners();
});

test('destroy trata falha de exclusao e retorna erro', function () {
    $t = Transacao::factory()->create();
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
