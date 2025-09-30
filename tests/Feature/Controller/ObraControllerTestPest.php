<?php

use App\Models\Imovel;
use App\Models\Obra;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Keep session/web middleware active
    $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
});

test('index retorna view com obras paginadas', function () {
    Obra::factory()->count(3)->create();

    $response = $this->get(route('obras.index'));

    $response->assertStatus(200);
    $response->assertViewIs('obras.index');
    $response->assertViewHas('obras');
});

test('create retorna view com imoveis', function () {
    Imovel::factory()->count(2)->create();

    $response = $this->get(route('obras.create'));

    $response->assertStatus(200);
    $response->assertViewIs('obras.create');
    $response->assertViewHas('imoveis');
});

test('store cria obra e redireciona', function () {
    $imovel = Imovel::factory()->create();

    $payload = [
        'descricao' => 'Reforma geral',
        'valor' => 1500,
        'data_inicio' => now()->toDateString(),
        'imovel_id' => $imovel->id,
    ];

    $response = $this->post(route('obras.store'), $payload);

    $response->assertRedirect(route('obras.index'));
    $this->assertDatabaseHas('obras', ['descricao' => 'Reforma geral', 'imovel_id' => $imovel->id]);
});

test('store valida campos obrigatórios', function () {
    $response = $this->post(route('obras.store'), [
        'valor' => -5,
    ]);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['descricao', 'valor', 'imovel_id']);
});

test('create e edit apresentam imoveis para seleção', function () {
    Imovel::factory()->count(2)->create();

    $this->get(route('obras.create'))->assertOk()->assertViewHas('imoveis');

    $obra = Obra::factory()->create();
    $this->get(route('obras.edit', $obra))->assertOk()->assertViewHasAll(['obra', 'imoveis']);
});

test('store retorna erro generico quando DB falha', function () {
    $imovel = Imovel::factory()->create();

    // simulate a DB failure during create by using a model event that throws
    \App\Models\Obra::creating(function () {
        throw new \Exception('fail');
    });

    $payload = [
        'descricao' => 'Reforma',
        'valor' => 100,
        'imovel_id' => $imovel->id,
    ];

    $response = $this->post(route('obras.store'), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors('general');
});

test('edit mostra formulario com obra e imoveis', function () {
    $obra = Obra::factory()->create();

    $response = $this->get(route('obras.edit', $obra));

    $response->assertStatus(200);
    $response->assertViewIs('obras.edit');
    $response->assertViewHasAll(['obra', 'imoveis']);
});

test('update altera obra e redireciona', function () {
    $obra = Obra::factory()->create(['descricao' => 'Velha']);
    $imovel = Imovel::factory()->create();

    $payload = [
        'descricao' => 'Atualizada',
        'valor' => 200,
        'imovel_id' => $imovel->id,
    ];

    $response = $this->put(route('obras.update', $obra), $payload);

    $response->assertRedirect(route('obras.index'));
    $this->assertDatabaseHas('obras', ['id' => $obra->id, 'descricao' => 'Atualizada']);
});

test('update retorna erro generico quando DB falha', function () {
    $obra = Obra::factory()->create(['descricao' => 'Velha']);
    $imovel = Imovel::factory()->create();

    // simulate a DB failure during update using model event
    \App\Models\Obra::updating(function () {
        throw new \Exception('fail');
    });

    $payload = [
        'descricao' => 'Atualizada',
        'valor' => 200,
        'imovel_id' => $imovel->id,
    ];

    $response = $this->put(route('obras.update', $obra), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors('general');
});

test('destroy retorna erro generico quando DB falha', function () {
    $obra = Obra::factory()->create();

    // simulate a DB failure during delete using model event
    \App\Models\Obra::deleting(function () {
        throw new \Exception('fail');
    });

    $response = $this->delete(route('obras.destroy', $obra));

    $response->assertRedirect(route('obras.index'));
    $response->assertSessionHasErrors('general');
});

test('update valida campos obrigatórios e retorna erros', function () {
    $obra = Obra::factory()->create(['descricao' => 'Velha']);
    $imovel = Imovel::factory()->create();

    // missing descricao and invalid valor
    $payload = [
        'valor' => -10,
        'imovel_id' => $imovel->id,
    ];

    $response = $this->put(route('obras.update', $obra), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['descricao', 'valor']);
});

test('index suporta paginação e retorna view', function () {
    // create more than 15 records to force pagination
    Obra::factory()->count(20)->create();

    $response = $this->get(route('obras.index', ['page' => 2]));

    $response->assertOk();
    $response->assertViewIs('obras.index');
    $response->assertViewHas('obras');
});

test('store valida data_fim deve ser after_or_equal data_inicio', function () {
    $imovel = Imovel::factory()->create();

    $payload = [
        'descricao' => 'Teste datas',
        'valor' => 200,
        'data_inicio' => '2025-10-10',
        'data_fim' => '2025-10-09', // antes
        'imovel_id' => $imovel->id,
    ];

    $response = $this->post(route('obras.store'), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['data_fim']);
});

test('store aceita data_fim igual a data_inicio', function () {
    $imovel = Imovel::factory()->create();

    $payload = [
        'descricao' => 'Teste datas igual',
        'valor' => 300,
        'data_inicio' => '2025-10-10',
        'data_fim' => '2025-10-10',
        'imovel_id' => $imovel->id,
    ];

    $response = $this->post(route('obras.store'), $payload);
    $response->assertRedirect(route('obras.index'));
    $this->assertDatabaseHas('obras', ['descricao' => 'Teste datas igual']);
});

test('destroy exclui obra e redireciona', function () {
    $obra = Obra::factory()->create();

    $response = $this->delete(route('obras.destroy', $obra));

    $response->assertRedirect(route('obras.index'));
    $this->assertDatabaseMissing('obras', ['id' => $obra->id]);
});
