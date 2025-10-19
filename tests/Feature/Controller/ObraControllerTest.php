<?php

use App\Http\Controllers\ObraController;
use App\Models\Imovel;
use App\Models\Obra;
use App\Models\Proprietario;
use App\Models\Propriedade;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use function Pest\Laravel\{get, post, put, delete, actingAs};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = Proprietario::factory()->create();
    $this->actingAs($this->user, 'proprietario');
    
    $this->propriedade = Propriedade::factory()->create(['proprietario_id' => $this->user->id]);
    $this->imovel = Imovel::factory()->create(['propriedade_id' => $this->propriedade->id]);
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
    Imovel::factory()->count(2)->create(['propriedade_id' => $this->propriedade->id]);

    $this->get(route('obras.create'))->assertOk()->assertViewHas('imoveis');

    $obra = Obra::factory()->create(['imovel_id' => $this->imovel->id]);
    $this->get(route('obras.edit', $obra))->assertOk()->assertViewHasAll(['obra', 'imoveis']);
});

test('store retorna erro generico quando DB falha', function () {
    $imovel = Imovel::factory()->create();

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
    $obra = Obra::factory()->create(['imovel_id' => $this->imovel->id]);

    $response = $this->get(route('obras.edit', $obra));

    $response->assertStatus(200);
    $response->assertViewIs('obras.edit');
    $response->assertViewHasAll(['obra', 'imoveis']);
});

test('update altera obra e redireciona', function () {
    $obra = Obra::factory()->create(['descricao' => 'Velha', 'imovel_id' => $this->imovel->id]);

    $payload = [
        'descricao' => 'Atualizada',
        'valor' => 200,
        'imovel_id' => $this->imovel->id,
    ];

    $response = $this->put(route('obras.update', $obra), $payload);

    $response->assertRedirect(route('obras.index'));
    $this->assertDatabaseHas('obras', ['id' => $obra->id, 'descricao' => 'Atualizada']);
});

test('update retorna erro generico quando DB falha', function () {
    $obra = Obra::factory()->create(['descricao' => 'Velha', 'imovel_id' => $this->imovel->id]);

    \App\Models\Obra::updating(function () {
        throw new \Exception('fail');
    });

    $payload = [
        'descricao' => 'Atualizada',
        'valor' => 200,
        'imovel_id' => $this->imovel->id,
    ];

    $response = $this->put(route('obras.update', $obra), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors('general');
});

test('destroy retorna erro generico quando DB falha', function () {
    $obra = Obra::factory()->create(['imovel_id' => $this->imovel->id]);

    \App\Models\Obra::deleting(function () {
        throw new \Exception('fail');
    });

    $response = $this->delete(route('obras.destroy', $obra));

    $response->assertRedirect(route('obras.index'));
    $response->assertSessionHasErrors('general');
});

test('update valida campos obrigatórios e retorna erros', function () {
    $obra = Obra::factory()->create(['descricao' => 'Velha', 'imovel_id' => $this->imovel->id]);

    
    $payload = [
        'valor' => -10,
        'imovel_id' => $this->imovel->id,
    ];

    $response = $this->put(route('obras.update', $obra), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors(['descricao', 'valor']);
});

test('index suporta paginação e retorna view', function () {
    
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
    $obra = Obra::factory()->create(['imovel_id' => $this->imovel->id]);

    $response = $this->delete(route('obras.destroy', $obra));

    $response->assertRedirect(route('obras.index'));
    $this->assertDatabaseMissing('obras', ['id' => $obra->id]);
});

 
test('controlador de obra: chamadas diretas cobrem store, update e destroy', function () {
    $controller = new ObraController();

    
    $reqStore = Request::create('/obras', 'POST', ['descricao' => 'Direct', 'valor' => 100, 'imovel_id' => $this->imovel->id]);
    $resStore = $controller->store($reqStore);
    expect($resStore)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('obras', ['descricao' => 'Direct']);

    $obra = Obra::first();

    
    $reqUpdate = Request::create('/obras/' . $obra->id, 'PUT', ['descricao' => 'Direct Updated', 'valor' => 200, 'imovel_id' => $this->imovel->id]);
    $resUpdate = $controller->update($reqUpdate, $obra);
    expect($resUpdate)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('obras', ['id' => $obra->id, 'descricao' => 'Direct Updated']);

    
    $resDestroy = $controller->destroy($obra);
    expect($resDestroy)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);

    
});

test('controlador de obra: index e create (invocação direta) retornam views', function () {
    $controller = new ObraController();

    $req = Request::create('/obras', 'GET');
    $res = $controller->index($req);
    expect($res)->toBeInstanceOf(Illuminate\View\View::class);

    $resCreate = $controller->create();
    expect($resCreate)->toBeInstanceOf(Illuminate\View\View::class);
});

test('controlador de obra: store, update e destroy via métodos do controller (invocação direta)', function () {
    $controller = new ObraController();

    
    $reqStore = Request::create('/obras', 'POST', ['descricao' => 'Extra Direct', 'valor' => 500, 'imovel_id' => $this->imovel->id]);
    $resStore = $controller->store($reqStore);
    expect($resStore)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('obras', ['descricao' => 'Extra Direct']);

    $obra = Obra::where('descricao', 'Extra Direct')->first();

    
    $reqUpdate = Request::create('/obras/' . $obra->id, 'PUT', ['descricao' => 'Direct Updated Extra', 'valor' => 600, 'imovel_id' => $this->imovel->id]);
    $resUpdate = $controller->update($reqUpdate, $obra);
    expect($resUpdate)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('obras', ['id' => $obra->id, 'descricao' => 'Direct Updated Extra']);

    
    $resEdit = $controller->edit($obra);
    expect($resEdit)->toBeInstanceOf(Illuminate\View\View::class);

    
    $resDestroy = $controller->destroy($obra);
    expect($resDestroy)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseMissing('obras', ['id' => $obra->id]);
});
