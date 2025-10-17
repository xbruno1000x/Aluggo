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
            'nome' => 'Locatario Teste',
            'telefone' => '+5511988887777',
            'email' => 'tenant@example.com',
        ];
    }
}