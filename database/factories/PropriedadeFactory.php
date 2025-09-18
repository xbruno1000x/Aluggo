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
            'endereco' => $this->faker->address(),
            'descricao' => $this->faker->sentence(),
            'proprietario_id' => Proprietario::factory(),
        ];
    }
}
