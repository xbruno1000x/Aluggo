<?php

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware();
});

test('store cria aluguel e seta status do imovel para alugado quando ativo hoje', function () {
    $imovel = Imovel::factory()->create(['status' => 'disponivel']);
    $locatario = Locatario::factory()->create();

    $data = [
        'imovel_id' => $imovel->id,
        'locatario_id' => $locatario->id,
        'valor_mensal' => 1500,
        'data_inicio' => Carbon::today()->toDateString(),
        'data_fim' => Carbon::today()->addMonth()->toDateString(),
    ];

    $response = $this->post(route('alugueis.store'), $data);

    $response->assertRedirect(route('alugueis.index'));
    $this->assertDatabaseHas('alugueis', [
        'imovel_id' => $imovel->id,
        'locatario_id' => $locatario->id,
        'valor_mensal' => 1500,
    ]);

    $imovel->refresh();
    $this->assertEquals('alugado', $imovel->status);
});

test('store permite contratos não ativos sem alterar status do imovel', function () {
    $imovel = Imovel::factory()->create(['status' => 'disponivel']);
    $locatario = Locatario::factory()->create();

    $data = [
        'imovel_id' => $imovel->id,
        'locatario_id' => $locatario->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::today()->addDays(10)->toDateString(),
        'data_fim' => Carbon::today()->addDays(20)->toDateString(),
    ];

    $this->post(route('alugueis.store'), $data)->assertRedirect(route('alugueis.index'));

    $imovel->refresh();
    $this->assertEquals('disponivel', $imovel->status);
});
