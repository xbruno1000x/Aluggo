<?php

use App\Models\Imovel;
use App\Models\Propriedade;
use App\Models\Proprietario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // keep session middleware active
    $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
    $this->user = Proprietario::factory()->create();
    // set as authenticated user (Auth::id() will return this id in controller)
    $this->be($this->user, 'proprietario');
});

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

    $response->assertRedirect(route('imoveis.index'));
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
