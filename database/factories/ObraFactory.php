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
        $start = now()->subDays(30);
        $end = now()->addDays(10);

        return [
            'descricao' => 'Obra de teste',
            'valor' => 5000.00,
            'data_inicio' => $start->toDateString(),
            'data_fim' => $end->toDateString(),
            'imovel_id' => Imovel::factory(),
        ];
    }
}
