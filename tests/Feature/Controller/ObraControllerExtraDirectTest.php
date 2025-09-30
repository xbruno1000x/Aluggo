<?php

use App\Http\Controllers\ObraController;
use App\Models\Imovel;
use App\Models\Obra;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('obra controller direct index e create retornam views', function () {
    $controller = new ObraController();

    $req = Request::create('/obras', 'GET');
    $res = $controller->index($req);
    expect($res)->toBeInstanceOf(Illuminate\View\View::class);

    $resCreate = $controller->create();
    expect($resCreate)->toBeInstanceOf(Illuminate\View\View::class);
});

test('obra controller direct store update destroy via controller methods', function () {
    $controller = new ObraController();

    $imovel = Imovel::factory()->create();

    // store
    $reqStore = Request::create('/obras', 'POST', ['descricao' => 'Extra Direct', 'valor' => 500, 'imovel_id' => $imovel->id]);
    $resStore = $controller->store($reqStore);
    expect($resStore)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('obras', ['descricao' => 'Extra Direct']);

    $obra = Obra::where('descricao', 'Extra Direct')->first();

    // update
    $reqUpdate = Request::create('/obras/' . $obra->id, 'PUT', ['descricao' => 'Direct Updated Extra', 'valor' => 600, 'imovel_id' => $imovel->id]);
    $resUpdate = $controller->update($reqUpdate, $obra);
    expect($resUpdate)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('obras', ['id' => $obra->id, 'descricao' => 'Direct Updated Extra']);

    // edit
    $resEdit = $controller->edit($obra);
    expect($resEdit)->toBeInstanceOf(Illuminate\View\View::class);

    // destroy
    $resDestroy = $controller->destroy($obra);
    expect($resDestroy)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseMissing('obras', ['id' => $obra->id]);
});
