<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Taxa;
use App\Models\Imovel;
use App\Models\Taxa as TaxaModel;

uses(RefreshDatabase::class);

it('possui relações e casts corretamente', function () {
    $imovel = Imovel::factory()->create();
    $taxa = Taxa::factory()->create(['imovel_id' => $imovel->id, 'valor' => 123.45]);

    expect($taxa->imovel)->toBeInstanceOf(Imovel::class);
    expect((float) $taxa->valor)->toBe(123.45);
});
