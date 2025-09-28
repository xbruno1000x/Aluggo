<?php

namespace Database\Factories;

use App\Models\Obra;
use App\Models\Imovel;
use Illuminate\Database\Eloquent\Factories\Factory;

class ObraFactory extends Factory
{
    protected $model = Obra::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-1 years', 'now');
        $end = (clone $start)->modify('+'.rand(1,60).' days');

        return [
            'descricao' => $this->faker->sentence(6),
            'valor' => $this->faker->randomFloat(2, 1000, 50000),
            'data_inicio' => $start->format('Y-m-d'),
            'data_fim' => $end->format('Y-m-d'),
            'imovel_id' => Imovel::factory(),
        ];
    }
}
