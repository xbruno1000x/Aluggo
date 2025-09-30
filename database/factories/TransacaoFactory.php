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
            'valor_venda' => $this->faker->numberBetween(50000, 2000000),
            'data_venda' => Carbon::today()->subDays($this->faker->numberBetween(0, 365)),
            'imovel_id' => Imovel::factory(),
        ];
    }
}
