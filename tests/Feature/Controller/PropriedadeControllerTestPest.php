<?php

use App\Models\Propriedade;
use App\Models\Proprietario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    $this->user = Proprietario::factory()->create();
    $this->be($this->user, 'proprietario');
});

test('index retorna propriedades do usuario e busca funciona', function () {
    Propriedade::factory()->create(['nome' => 'Casa Teste', 'proprietario_id' => $this->user->id]);
    Propriedade::factory()->create(['nome' => 'Outra', 'proprietario_id' => $this->user->id]);

    $response = $this->get(route('propriedades.index', ['search' => 'Casa']));

    $response->assertStatus(200);
    $response->assertSee('Casa Teste');
});

test('store cria propriedade e retorna JSON', function () {
    $payload = [
        'nome' => 'Nova Prop',
        'endereco' => 'Rua A',
        'bairro' => 'Centro',
    ];

    $response = $this->postJson(route('propriedades.store'), $payload);

    $response->assertStatus(200);
    $response->assertJsonStructure(['id', 'nome']);
    $this->assertDatabaseHas('propriedades', ['nome' => 'Nova Prop']);
});

test('edit encontra propriedade do usuario', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);

    $response = $this->get(route('propriedades.edit', $prop->id));

    $response->assertStatus(200);
    $response->assertViewHas('propriedade');
});

test('update altera propriedade do usuario', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id, 'nome' => 'Old']);

    $payload = ['nome' => 'Updated', 'endereco' => 'Rua B', 'bairro' => 'Zona'];
    $response = $this->put(route('propriedades.update', $prop->id), $payload);

    $response->assertRedirect(route('propriedades.index'));
    $this->assertDatabaseHas('propriedades', ['id' => $prop->id, 'nome' => 'Updated']);
});

test('destroy remove propriedade do usuario', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);

    $response = $this->delete(route('propriedades.destroy', $prop->id));

    $response->assertRedirect(route('propriedades.index'));
    $this->assertDatabaseMissing('propriedades', ['id' => $prop->id]);
});
