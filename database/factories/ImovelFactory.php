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
            'nome' => 'Imóvel ' . $this->faker->randomNumber(3),
            'numero' => (string) $this->faker->numberBetween(1, 9999),
            'tipo' => 'Apartamento',
            'valor_compra' => 250000,
            // Default to 'disponivel' for deterministic tests; tests can override when needed
            'status' => 'disponivel',
            'data_aquisicao' => now()->toDateString(),
            'propriedade_id' => Propriedade::factory(),
        ];
    }
}