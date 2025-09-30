<?php

use App\Models\Obra;
use App\Models\Imovel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('obra factory cria instancia válida', function () {
    $imovel = Imovel::factory()->create();
    $obra = Obra::factory()->create(['imovel_id' => $imovel->id]);

    expect($obra)->toBeInstanceOf(Obra::class)
        ->and($obra->descricao)->not->toBeEmpty()
        ->and($obra->imovel_id)->toBe($imovel->id);
});

test('obra pertence a um imovel (relação)', function () {
    $imovel = Imovel::factory()->create();
    $obra = Obra::factory()->create(['imovel_id' => $imovel->id]);

    expect($obra->imovel)->toBeInstanceOf(Imovel::class)
        ->and($obra->imovel->id)->toBe($imovel->id);
});
