<?php

use App\Models\Locatario;
use App\Models\Aluguel;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('locatario factory cria instancia válida', function () {
    $loc = Locatario::factory()->create();

    expect($loc)->toBeInstanceOf(Locatario::class)
        ->and($loc->nome)->not->toBeEmpty()
        ->and($loc->id)->toBeInt();
});

test('locatario possui aluguels (relação)', function () {
    $loc = Locatario::factory()->create();
    Aluguel::factory()->count(2)->create(['locatario_id' => $loc->id]);

    expect($loc->alugueis)->toBeIterable()
        ->and($loc->alugueis->count())->toBe(2)
        ->and($loc->alugueis->first())->toBeInstanceOf(Aluguel::class);
});
