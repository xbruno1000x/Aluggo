<?php

use App\Http\Controllers\PropriedadeController;
use App\Models\Propriedade;
use App\Models\Proprietario;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{actingAs};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = Proprietario::factory()->create();
});

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

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    $this->be($this->user, 'proprietario');
});

test('index retorna propriedades do usuário e busca funciona', function () {
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

test('edit encontra propriedade do usuário', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);

    $response = $this->get(route('propriedades.edit', $prop->id));

    $response->assertStatus(200);
    $response->assertViewHas('propriedade');
});

test('update altera propriedade do usuário', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id, 'nome' => 'Old']);

    $payload = ['nome' => 'Updated', 'endereco' => 'Rua B', 'bairro' => 'Zona'];
    $response = $this->put(route('propriedades.update', $prop->id), $payload);

    $response->assertRedirect(route('propriedades.index'));
    $this->assertDatabaseHas('propriedades', ['id' => $prop->id, 'nome' => 'Updated']);
});

test('destroy remove propriedade do usuário', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);

    $response = $this->delete(route('propriedades.destroy', $prop->id));

    $response->assertRedirect(route('propriedades.index'));
    $this->assertDatabaseMissing('propriedades', ['id' => $prop->id]);
});

test('controlador de propriedade: cobre editar, atualizar, excluir e autorização do proprietário', function () {
    /** @var \App\Models\Proprietario $user */
    $user = Proprietario::factory()->create();
    $controller = new PropriedadeController();
    \Illuminate\Support\Facades\Auth::guard('proprietario')->login($user);

    $prop = Propriedade::factory()->create(['proprietario_id' => $user->id]);

    // editar (funciona quando é o proprietário)
    $resEdit = $controller->edit($prop->id);
    expect($resEdit)->toBeInstanceOf(Illuminate\View\View::class);

    // atualizar (atualização)
    $reqUpdate = Request::create('/propriedades/' . $prop->id, 'PUT', ['nome' => 'X', 'endereco' => 'Y', 'bairro' => 'Z']);
    $resUpdate = $controller->update($reqUpdate, $prop->id);
    expect($resUpdate)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);

    // destruir (exclusão)
    $resDestroy = $controller->destroy($prop->id);
    expect($resDestroy)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
});
