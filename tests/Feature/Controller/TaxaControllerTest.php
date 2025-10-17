<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Proprietario;
use App\Models\Propriedade;
use App\Models\Imovel;
use App\Models\Taxa;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->owner = Proprietario::factory()->create();
    $this->actingAs($this->owner, 'proprietario');
    $this->prop = Propriedade::factory()->create(['proprietario_id' => $this->owner->id]);
    $this->imovel = Imovel::factory()->create(['propriedade_id' => $this->prop->id]);
});

it('permite que o proprietário crie e gerencie taxas', function () {

    $res = $this->get(route('taxas.create'));
    $res->assertStatus(200);

    $post = [
        'imovel_id' => $this->imovel->id,
        'tipo' => 'condominio',
        'valor' => 150.50,
        'data_pagamento' => now()->toDateString(),
        'pagador' => 'proprietario',
    ];

    $res = $this->post(route('taxas.store'), $post);
    $res->assertRedirect(route('taxas.index'));
    $this->assertDatabaseHas('taxas', ['imovel_id' => $this->imovel->id, 'valor' => 150.50]);

    $taxa = Taxa::first();

    $res = $this->get(route('taxas.edit', $taxa));
    $res->assertStatus(200);

    $res = $this->put(route('taxas.update', $taxa), array_merge($post, ['valor' => 200]));
    $res->assertRedirect(route('taxas.index'));
    $this->assertDatabaseHas('taxas', ['id' => $taxa->id, 'valor' => 200]);


    $res = $this->delete(route('taxas.destroy', $taxa));
    $res->assertRedirect(route('taxas.index'));
    $this->assertDatabaseMissing('taxas', ['id' => $taxa->id]);
});

test('index suporta filtros e retorna a view', function () {
    Taxa::factory()->create(['imovel_id' => $this->imovel->id, 'data_pagamento' => now()->toDateString(), 'pagador' => 'proprietario']);

    $res = $this->get(route('taxas.index', ['imovel_id' => $this->imovel->id]));
    $res->assertStatus(200)->assertViewIs('taxas.index');

    $res = $this->get(route('taxas.index', ['pagador' => 'proprietario']));
    $res->assertStatus(200);

    $res = $this->get(route('taxas.index', ['tipo' => 'condominio']));
    $res->assertStatus(200);

    $res = $this->get(route('taxas.index', ['start' => now()->subDay()->toDateString(), 'end' => now()->addDay()->toDateString()]));
    $res->assertStatus(200);
});

test('store falha quando imovel_id e propriedade_id são fornecidos simultaneamente', function () {
    $post = [
        'imovel_id' => $this->imovel->id,
        'propriedade_id' => $this->prop->id,
        'tipo' => 'condominio',
        'valor' => 100,
        'data_pagamento' => now()->toDateString(),
        'pagador' => 'proprietario',
    ];

    $res = $this->post(route('taxas.store'), $post);
    $res->assertSessionHas('error');
});

test('store aborta quando propriedade não pertence ao proprietário', function () {
    $other = Proprietario::factory()->create();
    $otherProp = Propriedade::factory()->create(['proprietario_id' => $other->id]);

    $post = [
        'propriedade_id' => $otherProp->id,
        'tipo' => 'iptu',
        'valor' => 200,
        'data_pagamento' => now()->toDateString(),
        'pagador' => 'proprietario',
    ];

    $this->post(route('taxas.store'), $post)->assertStatus(403);
});

test('proprietário pode criar, atualizar e excluir taxa', function () {
    $post = [
        'imovel_id' => $this->imovel->id,
        'tipo' => 'condominio',
        'valor' => 150.50,
        'data_pagamento' => now()->toDateString(),
        'pagador' => 'proprietario',
    ];

    $res = $this->post(route('taxas.store'), $post);
    $res->assertRedirect(route('taxas.index'));

    $taxa = Taxa::first();
    $this->get(route('taxas.edit', $taxa))->assertStatus(200);

    $this->put(route('taxas.update', $taxa), array_merge($post, ['valor' => 300]))->assertRedirect(route('taxas.index'));
    $taxa->refresh();
    expect((float)$taxa->valor)->toBe(300.0);

    $this->delete(route('taxas.destroy', $taxa))->assertRedirect(route('taxas.index'));
    expect(Taxa::find($taxa->id))->toBeNull();
});

test('update aborta quando altera propriedade para uma que não pertence', function () {
    $taxa = Taxa::factory()->create(['imovel_id' => $this->imovel->id, 'proprietario_id' => $this->owner->id]);
    $other = Proprietario::factory()->create();
    $otherProp = Propriedade::factory()->create(['proprietario_id' => $other->id]);

    $this->put(route('taxas.update', $taxa), ['propriedade_id' => $otherProp->id, 'tipo' => 'iptu', 'valor' => 10, 'data_pagamento' => now()->toDateString(), 'pagador' => 'proprietario'])
        ->assertStatus(403);
});
