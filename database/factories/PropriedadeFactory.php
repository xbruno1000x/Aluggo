<?php

namespace Database\Factories;

use App\Models\Propriedade;
use App\Models\Proprietario;
use Illuminate\Database\Eloquent\Factories\Factory;

class PropriedadeFactory extends Factory
{
    protected $model = Propriedade::class;

    public function definition(): array
    {
        return [
            'nome' => $this->faker->company(),
            'endereco' => $this->faker->streetAddress(),
            'cep' => $this->faker->postcode(),
            'cidade' => $this->faker->city(),
            'estado' => $this->faker->state(),
            'descricao' => $this->faker->sentence(),
            'proprietario_id' => Proprietario::factory(),
        ];
    }
}
