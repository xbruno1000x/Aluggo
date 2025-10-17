<?php

namespace Database\Factories;

use App\Models\Proprietario;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProprietarioFactory extends Factory
{
    protected $model = Proprietario::class;

    public function definition(): array
    {
        $uniqueSuffix = strtolower((string) Str::ulid());
        return [
            'nome' => 'Proprietario Teste ' . $uniqueSuffix,
            'cpf' => $this->faker->unique()->numerify('###########'),
            'telefone' => '+55' . $this->faker->numerify('11#########'),
            'email' => 'owner+' . $uniqueSuffix . '@example.com',
            'password' => Hash::make('password'),
        ];
    }
}