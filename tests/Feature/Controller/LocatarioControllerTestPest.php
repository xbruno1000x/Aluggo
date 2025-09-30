<?php

use App\Models\Locatario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Disable only the authentication middleware so session and other web middleware
    // (which provide the $errors variable and sessions) remain active during tests.
    $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
});

test('index retorna view com locatários', function () {
    Locatario::factory()->count(3)->create();

    $response = $this->get(route('locatarios.index'));

    $response->assertStatus(200);
    $response->assertViewIs('locatarios.index');
    $response->assertViewHas('locatarios');
});

test('create retorna a view de cadastro', function () {
    $response = $this->get(route('locatarios.create'));

    $response->assertStatus(200);
    $response->assertViewIs('locatarios.create');
});

test('store cria um locatário e redireciona', function () {
    $payload = [
        'nome' => 'João Teste',
        'telefone' => '11999999999',
        'email' => 'joao@example.com',
    ];

    $response = $this->post(route('locatarios.store'), $payload);

    $response->assertRedirect(route('locatarios.index'));
    $this->assertDatabaseHas('locatarios', ['nome' => 'João Teste', 'email' => 'joao@example.com']);
});

test('store valida campos obrigatórios', function () {
    $response = $this->post(route('locatarios.store'), [
        'telefone' => '11999999999'
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors('nome');
});

test('edit retorna view com locatário', function () {
    $loc = Locatario::factory()->create();

    $response = $this->get(route('locatarios.edit', $loc));

    $response->assertStatus(200);
    $response->assertViewIs('locatarios.edit');
    $response->assertViewHas('locatario');
});

test('update modifica locatário e redireciona', function () {
    $loc = Locatario::factory()->create(['nome' => 'Antigo Nome']);

    $payload = [
        'nome' => 'Nome Atualizado',
        'telefone' => $loc->telefone,
        'email' => $loc->email,
    ];

    $response = $this->put(route('locatarios.update', $loc), $payload);

    $response->assertRedirect(route('locatarios.index'));
    $this->assertDatabaseHas('locatarios', ['id' => $loc->id, 'nome' => 'Nome Atualizado']);
});

test('destroy exclui locatário', function () {
    $loc = Locatario::factory()->create();

    $response = $this->delete(route('locatarios.destroy', $loc));

    $response->assertRedirect(route('locatarios.index'));
    $this->assertDatabaseMissing('locatarios', ['id' => $loc->id]);
});
