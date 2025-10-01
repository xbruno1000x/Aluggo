<?php

namespace Database\Factories;

use App\Models\Proprietario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class ProprietarioFactory extends Factory
{
    protected $model = Proprietario::class;

    public function definition(): array
    {
        return [
            'nome' => $this->faker->name(),
            'cpf' => $this->faker->numerify('###########'),
            // Ensure telefone fits validation (max 15 chars)
            'telefone' => substr(preg_replace('/[^0-9+]/', '', $this->faker->phoneNumber()), 0, 15),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => Hash::make('password'),
        ];
    }
}