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
        $uniq = substr((string) time() . random_int(100, 999), -10);
        return [
            'nome' => 'Proprietario Teste ' . $uniq,
            'cpf' => str_pad((string) random_int(10000000000, 99999999999), 11, '0', STR_PAD_LEFT),
            // Ensure telefone fits validation (max 15 chars)
            'telefone' => '+5511' . random_int(900000000, 999999999),
            'email' => 'owner+' . $uniq . '@example.com',
            'password' => password_hash('password', PASSWORD_BCRYPT),
        ];
    }
}