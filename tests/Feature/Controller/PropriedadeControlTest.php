<?php

use App\Models\Propriedade;
use App\Models\Proprietario;
use function Pest\Laravel\{actingAs};

beforeEach(fn () => $this->user = Proprietario::factory()->create());

test('lista propriedades do usuário', function () {
    Propriedade::factory()->count(2)->create(['proprietario_id' => $this->user->id]);

    actingAs($this->user)
        ->get(route('propriedades.index'))
        ->assertOk()
        ->assertViewHas('propriedades');
});

test('cria uma propriedade via JSON', function () {
    actingAs($this->user)
        ->postJson(route('propriedades.store'), [
            'nome' => 'Casa Teste',
            'endereco' => 'Rua Teste, 123',
            'bairro' => 'Bairro Teste',
            'descricao' => 'Descrição da casa teste',
        ])->assertJsonStructure(['id', 'nome']);
});