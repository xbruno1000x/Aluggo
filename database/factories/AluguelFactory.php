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
        $startDate = now()->subMonths(1);
        $endDate = null;

        return [
            'valor_mensal' => 1000.00,
            'caucao' => $this->faker->optional(0.7)->randomFloat(2, 500, 3000), // 70% chance de ter caução
            'data_inicio' => $startDate->toDateString(),
            'data_fim' => $endDate,
            'imovel_id' => Imovel::factory(),
            'locatario_id' => Locatario::factory(),
        ];
    }
}
