<?php

namespace Database\Factories;

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use Illuminate\Database\Eloquent\Factories\Factory;

class AluguelFactory extends Factory
{
    protected $model = Aluguel::class;

    public function definition(): array
    {
        $startDate = $this->faker->dateTimeBetween('-2 years', 'now');
        $endDate = $this->faker->boolean(70) ? $this->faker->dateTimeBetween($startDate, '+2 years') : null;

        return [
            'valor_mensal' => $this->faker->randomFloat(2, 500, 5000),
            'data_inicio' => $startDate->format('Y-m-d'),
            'data_fim' => $endDate ? $endDate->format('Y-m-d') : null,
            'imovel_id' => Imovel::factory(),
            'locatario_id' => Locatario::factory(),
        ];
    }
}
