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
            'cpf' => $this->generateValidCPF(),
            'telefone' => $this->faker->numerify('11#########'), // 11 dígitos (DDD + número)
            'email' => 'owner+' . $uniqueSuffix . '@example.com',
            'password' => Hash::make('password'),
        ];
    }

    /**
     * Gera um CPF válido para testes
     */
    private function generateValidCPF(): string
    {
        // Gera os 9 primeiros dígitos aleatórios
        $cpf = '';
        for ($i = 0; $i < 9; $i++) {
            $cpf .= mt_rand(0, 9);
        }

        // Calcula o primeiro dígito verificador
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;
        $cpf .= $digit1;

        // Calcula o segundo dígito verificador
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ((int) $cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;
        $cpf .= $digit2;

        return $cpf;
    }
}