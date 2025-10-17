<?php

namespace Database\Factories;

use App\Models\Transacao;
use App\Models\Imovel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Carbon\Carbon;

class TransacaoFactory extends Factory
{
    protected $model = Transacao::class;

    public function definition(): array
    {
        return [
            'valor_venda' => 200000,
            'data_venda' => Carbon::today()->subDays(10),
            'imovel_id' => Imovel::factory(),
        ];
    }
}
