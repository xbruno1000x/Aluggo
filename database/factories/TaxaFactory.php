<?php

namespace Database\Factories;

use App\Models\Taxa;
use App\Models\Imovel;
use App\Models\Aluguel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class TaxaFactory extends Factory
{
    protected $model = Taxa::class;

    public function definition(): array
    {
        $imovel = Imovel::factory()->create();
        return [
            'imovel_id' => $imovel->id,
            'aluguel_id' => null,
            'tipo' => 'condominio',
            'valor' => 100.00,
            'data_pagamento' => Carbon::now()->subDays(rand(0, 365))->toDateString(),
            'pagador' => 'proprietario',
            'observacao' => 'observacao',
        ];
    }
}
