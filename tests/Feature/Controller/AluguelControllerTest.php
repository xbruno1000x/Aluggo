<?php

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Desabilita apenas o middleware de autenticação para manter sessão/erro
    // (útil para asserções que dependem de session/errors) durante os testes.
    $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
});

test('store rejeita contratos sobrepostos', function () {
    $imovel = Imovel::factory()->create(['status' => 'disponivel']);
    $loc1 = Locatario::factory()->create();
    $loc2 = Locatario::factory()->create();

    Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc1->id,
        'data_inicio' => '2025-10-22',
        'data_fim' => '2025-10-28',
        'valor_mensal' => 1000,
    ]);

    $payload = [
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc2->id,
        'valor_mensal' => 1100,
        'data_inicio' => '2025-10-24',
        'data_fim' => '2025-10-27',
    ];

    $response = $this->post(route('alugueis.store'), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors('imovel_id');

    $this->assertDatabaseCount('alugueis', 1);
});

test('index retorna view com alugueis', function () {
    Aluguel::factory()->count(3)->create();

    $response = $this->get(route('alugueis.index'));

    $response->assertStatus(200);
    $response->assertViewIs('alugueis.index');
    $response->assertViewHas('alugueis');
});
