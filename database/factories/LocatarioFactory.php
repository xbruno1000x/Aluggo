<?php

namespace Database\Factories;

use App\Models\Locatario;
use Illuminate\Database\Eloquent\Factories\Factory;

class LocatarioFactory extends Factory
{
    protected $model = Locatario::class;

    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
            'telefone' => $this->faker->phoneNumber(),
            'email' => $this->faker->unique()->safeEmail(),
        ];
    }
}