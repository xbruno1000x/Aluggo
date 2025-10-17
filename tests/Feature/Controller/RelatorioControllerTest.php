<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Proprietario;
use App\Models\Propriedade;
use App\Models\Imovel;
use App\Models\Aluguel;
use App\Models\Taxa;

uses(RefreshDatabase::class);

it('renderiza índice de relatórios e inclui taxas nos agregados', function () {
    $prop = Proprietario::factory()->create();
    $this->actingAs($prop, 'proprietario');

    $propriedade = Propriedade::factory()->create(['proprietario_id' => $prop->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $aluguel = Aluguel::factory()->create(['imovel_id' => $imovel->id]);

    Taxa::factory()->create([
        'imovel_id' => $imovel->id,
        'proprietario_id' => $prop->id,
        'valor' => 100.00,
        'pagador' => 'proprietario',
        'data_pagamento' => now()->toDateString(),
    ]);

    $response = $this->get(route('relatorios.index'));
    $response->assertStatus(200);
    $response->assertViewIs('relatorios.index');

    $data = $response->viewData('data');
    expect($data)->toHaveKey('aggregates');
    expect($data['aggregates']['taxasTotal'])->toBe(100.0);
});
