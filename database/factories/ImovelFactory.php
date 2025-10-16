<?php

namespace Database\Factories;

use App\Models\Imovel;
use App\Models\Propriedade;
use Illuminate\Database\Eloquent\Factories\Factory;

class ImovelFactory extends Factory
{
    protected $model = Imovel::class;

    public function definition(): array
    {
        return [
            'nome' => $this->faker->word() . ' ' . $this->faker->randomNumber(3),
            'numero' => (string) $this->faker->numberBetween(1, 9999),
            'tipo' => $this->faker->randomElement(['Apartamento', 'Loja', 'Terreno']),
            'valor_compra' => $this->faker->numberBetween(100000, 1000000),
            // Default to 'disponivel' for deterministic tests; tests can override when needed
            'status' => 'disponivel',
            'data_aquisicao' => $this->faker->date(),
            'propriedade_id' => Propriedade::factory(),
        ];
    }
}