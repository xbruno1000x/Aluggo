<?php

use App\Http\Controllers\ImovelController;
use App\Models\Imovel;
use App\Models\Propriedade;
use App\Models\Proprietario;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use function Pest\Laravel\{actingAs};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    $this->user = Proprietario::factory()->create();
    $this->be($this->user, 'proprietario');
});

test('cria um imóvel', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);

    actingAs($this->user)
        ->post(route('imoveis.store'), [
            'nome' => 'Imóvel Teste',
            'tipo' => 'apartamento',
            'status' => 'disponível',
            'propriedade_id' => $prop->id,
        ])->assertRedirect(route('imoveis.index'))
          ->assertSessionHas('success');
});

test('edita um imóvel', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $prop->id]);

    actingAs($this->user)
        ->get(route('imoveis.edit', $imovel))
        ->assertOk()
        ->assertViewHas('imovel');
});

test('lista imóveis do usuário', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    Imovel::factory()->count(3)->create(['propriedade_id' => $prop->id]);

    actingAs($this->user)
        ->get(route('imoveis.index'))
        ->assertOk()
        ->assertViewHas('imoveis');
});

// Tests ported from ImovelControllerTestPest
test('index retorna imoveis e propriedades do usuario', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    Imovel::factory()->count(2)->create(['propriedade_id' => $prop->id]);

    $response = $this->get(route('imoveis.index'));

    $response->assertStatus(200);
    $response->assertViewHasAll(['imoveis', 'propriedades']);
});

test('create retorna view com propriedades do usuario', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);

    $response = $this->get(route('imoveis.create'));

    $response->assertStatus(200);
    $response->assertViewHas('propriedades');
});

test('store valida e cria imovel quando propriedade pertence ao usuario', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);

    $payload = [
        'nome' => 'Teste Imovel',
        'numero' => 'A1',
        'tipo' => 'apartamento',
        'status' => 'disponível',
        'propriedade_id' => $prop->id,
    ];

    $response = $this->post(route('imoveis.store'), $payload);


    $this->assertDatabaseHas('imoveis', ['nome' => 'Teste Imovel', 'propriedade_id' => $prop->id]);
});

test('edit aborta se imovel nao pertence ao usuario', function () {
    $otherProp = Propriedade::factory()->create();
    $imovel = Imovel::factory()->create(['propriedade_id' => $otherProp->id]);

    $response = $this->get(route('imoveis.edit', $imovel));

    $response->assertStatus(403);
});

test('update aborta se imovel nao pertence ao usuario', function () {
    $otherProp = Propriedade::factory()->create();
    $imovel = Imovel::factory()->create(['propriedade_id' => $otherProp->id]);

    $payload = [
        'nome' => 'Novo Nome',
        'numero' => 'X1',
        'tipo' => 'apartamento',
        'status' => 'disponível',
        'propriedade_id' => $otherProp->id,
    ];

    $response = $this->put(route('imoveis.update', $imovel), $payload);

    $response->assertStatus(403);
});

test('destroy aborta se imovel nao pertence ao usuario', function () {
    $otherProp = Propriedade::factory()->create();
    $imovel = Imovel::factory()->create(['propriedade_id' => $otherProp->id]);

    $response = $this->delete(route('imoveis.destroy', $imovel));

    $response->assertStatus(403);
});

test('filters retornam resultados por nome e numero', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    Imovel::factory()->create(['propriedade_id' => $prop->id, 'nome' => 'FiltroNome', 'numero' => 'AAA']);

    $response = $this->get(route('imoveis.index', ['nome' => 'FiltroNome']));
    $response->assertOk()->assertViewHas('imoveis');

    $response2 = $this->get(route('imoveis.index', ['numero' => 'AAA']));
    $response2->assertOk()->assertViewHas('imoveis');
});

test('update e destroy funcionam para imovel do usuario', function () {
    $prop = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $prop->id]);

    $payload = ['nome' => 'Alterado Nome', 'numero' => 'B2', 'tipo' => 'apartamento', 'status' => 'disponível', 'propriedade_id' => $prop->id];
    $this->put(route('imoveis.update', $imovel), $payload)->assertRedirect(route('imoveis.index'));
    $this->assertDatabaseHas('imoveis', ['id' => $imovel->id, 'nome' => 'Alterado Nome']);

    $this->delete(route('imoveis.destroy', $imovel))->assertRedirect(route('imoveis.index'));
    $this->assertDatabaseMissing('imoveis', ['id' => $imovel->id]);
});

// Direct controller invocation test
test('controlador imovel: cobre filtros, store, update, destroy e checagem de propriedade', function () {
    /** @var \App\Models\Proprietario $user */
    $user = $this->user;
    Auth::guard('proprietario')->login($user);

    $controller = new ImovelController();

    $prop = Propriedade::factory()->create(['proprietario_id' => $user->id]);
    Imovel::factory()->create(['propriedade_id' => $prop->id, 'nome' => 'NomeFiltro', 'numero' => '123', 'tipo' => 'apartamento', 'status' => 'disponível']);

    // index com filtro por nome
    $req = Request::create('/imoveis', 'GET', ['nome' => 'NomeFiltro']);
    $res = $controller->index($req);
    expect($res)->toBeInstanceOf(Illuminate\View\View::class);

    // index com filtro por número
    $req2 = Request::create('/imoveis', 'GET', ['numero' => '123']);
    $res2 = $controller->index($req2);
    expect($res2)->toBeInstanceOf(Illuminate\View\View::class);

    // store (criação)
    $reqStore = Request::create('/imoveis', 'POST', ['nome' => 'Novo', 'tipo' => 'apartamento', 'status' => 'disponível', 'propriedade_id' => $prop->id]);
    $resStore = $controller->store($reqStore);
    expect($resStore)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('imoveis', ['nome' => 'Novo']);

    $imovel = Imovel::where('nome', 'Novo')->first();

    // update (atualização)
    $reqUpdate = Request::create('/imoveis/' . $imovel->id, 'PUT', ['nome' => 'Atual', 'tipo' => 'apartamento', 'status' => 'disponível', 'propriedade_id' => $prop->id]);
    $resUpdate = $controller->update($reqUpdate, $imovel);
    expect($resUpdate)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);

    // destroy (exclusão)
    $resDestroy = $controller->destroy($imovel);
    expect($resDestroy)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
});
