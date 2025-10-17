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
            'nome' => 'Propriedade ' . $this->faker->randomNumber(3),
            'endereco' => 'Rua Teste, 123',
            'cep' => '00000-000',
            'cidade' => 'Cidade',
            'estado' => 'Estado',
            'descricao' => 'Descrição de teste',
            'proprietario_id' => Proprietario::factory(),
        ];
    }
}
