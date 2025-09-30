<?php

use App\Http\Controllers\LocatarioController;
use App\Models\Locatario;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{get, post, put, delete};

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

test('controlador de locatário: chamadas diretas cobrem index, create, store, edit, update e destroy', function () {
    $controller = new LocatarioController();

    Locatario::factory()->count(2)->create();
    $req = Request::create('/locatarios', 'GET', []);
    $res = $controller->index($req);
    expect($res)->toBeInstanceOf(Illuminate\View\View::class);

    $req2 = Request::create('/locatarios', 'GET', ['search' => 'nome']);
    $res2 = $controller->index($req2);
    expect($res2)->toBeInstanceOf(Illuminate\View\View::class);

    $res3 = $controller->create();
    expect($res3)->toBeInstanceOf(Illuminate\View\View::class);

    $payload = ['nome' => 'Direct Loc', 'telefone' => '119', 'email' => 'direct@example.com'];
    $reqPost = Request::create('/locatarios', 'POST', $payload);
    $resPost = $controller->store($reqPost);
    expect($resPost)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('locatarios', ['nome' => 'Direct Loc']);

    $loc = Locatario::first();

    $resEdit = $controller->edit($loc);
    expect($resEdit)->toBeInstanceOf(Illuminate\View\View::class);

    $reqPut = Request::create('/locatarios/' . $loc->id, 'PUT', ['nome' => 'Alterado', 'telefone' => $loc->telefone, 'email' => $loc->email]);
    $resPut = $controller->update($reqPut, $loc);
    expect($resPut)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('locatarios', ['id' => $loc->id, 'nome' => 'Alterado']);

    $resDel = $controller->destroy($loc);
    expect($resDel)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseMissing('locatarios', ['id' => $loc->id]);
});
