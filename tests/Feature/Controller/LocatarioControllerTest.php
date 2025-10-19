<?php

use App\Http\Controllers\LocatarioController;
use App\Models\Locatario;
use App\Models\Proprietario;
use App\Models\Propriedade;
use App\Models\Imovel;
use App\Models\Aluguel;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{get, post, put, delete, actingAs};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = Proprietario::factory()->create();
    $this->actingAs($this->user, 'proprietario');
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
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc = Locatario::factory()->create();
    Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id
    ]);

    $response = $this->get(route('locatarios.edit', $loc));

    $response->assertStatus(200);
    $response->assertViewIs('locatarios.edit');
    $response->assertViewHas('locatario');
});

test('update modifica locatário e redireciona', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc = Locatario::factory()->create(['nome' => 'Antigo Nome']);
    Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id
    ]);

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
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc = Locatario::factory()->create();
    Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id
    ]);

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

    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc = Locatario::factory()->create();
    Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id
    ]);

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

test('index formata telefone celular com 11 digitos', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc = Locatario::factory()->create(['telefone' => '11987654321']);
    Aluguel::factory()->create(['imovel_id' => $imovel->id, 'locatario_id' => $loc->id]);

    $response = $this->get(route('locatarios.index'));

    $response->assertStatus(200);
    $locatarios = $response->viewData('locatarios');
    expect($locatarios->first()->telefone)->toBe('(11)98765-4321');
});

test('index formata telefone fixo com 10 digitos', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc = Locatario::factory()->create(['telefone' => '1134567890']);
    Aluguel::factory()->create(['imovel_id' => $imovel->id, 'locatario_id' => $loc->id]);

    $response = $this->get(route('locatarios.index'));

    $response->assertStatus(200);
    $locatarios = $response->viewData('locatarios');
    expect($locatarios->first()->telefone)->toBe('(11)3456-7890');
});

test('index mantém telefone vazio quando não há digitos', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc = Locatario::factory()->create(['telefone' => '']);
    Aluguel::factory()->create(['imovel_id' => $imovel->id, 'locatario_id' => $loc->id]);

    $response = $this->get(route('locatarios.index'));

    $response->assertStatus(200);
    $locatarios = $response->viewData('locatarios');
    expect($locatarios->first()->telefone)->toBe('');
});

test('index mantém formato original para telefones com formato diferente', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc = Locatario::factory()->create(['telefone' => '123456789']); // 9 dígitos
    Aluguel::factory()->create(['imovel_id' => $imovel->id, 'locatario_id' => $loc->id]);

    $response = $this->get(route('locatarios.index'));

    $response->assertStatus(200);
    $locatarios = $response->viewData('locatarios');
    expect($locatarios->first()->telefone)->toBe('123456789');
});

test('index busca por nome do locatario', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc1 = Locatario::factory()->create(['nome' => 'João Silva']);
    $loc2 = Locatario::factory()->create(['nome' => 'Maria Santos']);
    Aluguel::factory()->create(['imovel_id' => $imovel->id, 'locatario_id' => $loc1->id]);
    Aluguel::factory()->create(['imovel_id' => $imovel->id, 'locatario_id' => $loc2->id]);

    $response = $this->get(route('locatarios.index', ['search' => 'João']));

    $response->assertStatus(200);
    $locatarios = $response->viewData('locatarios');
    expect($locatarios)->toHaveCount(1);
    expect($locatarios->first()->nome)->toBe('João Silva');
});

test('index busca por telefone do locatario', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc1 = Locatario::factory()->create(['telefone' => '11987654321']);
    $loc2 = Locatario::factory()->create(['telefone' => '11912345678']);
    Aluguel::factory()->create(['imovel_id' => $imovel->id, 'locatario_id' => $loc1->id]);
    Aluguel::factory()->create(['imovel_id' => $imovel->id, 'locatario_id' => $loc2->id]);

    $response = $this->get(route('locatarios.index', ['search' => '87654']));

    $response->assertStatus(200);
    $locatarios = $response->viewData('locatarios');
    expect($locatarios)->toHaveCount(1);
});

test('index busca por email do locatario', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $loc1 = Locatario::factory()->create(['email' => 'joao@test.com']);
    $loc2 = Locatario::factory()->create(['email' => 'maria@test.com']);
    Aluguel::factory()->create(['imovel_id' => $imovel->id, 'locatario_id' => $loc1->id]);
    Aluguel::factory()->create(['imovel_id' => $imovel->id, 'locatario_id' => $loc2->id]);

    $response = $this->get(route('locatarios.index', ['search' => 'joao@']));

    $response->assertStatus(200);
    $locatarios = $response->viewData('locatarios');
    expect($locatarios)->toHaveCount(1);
    expect($locatarios->first()->email)->toBe('joao@test.com');
});
