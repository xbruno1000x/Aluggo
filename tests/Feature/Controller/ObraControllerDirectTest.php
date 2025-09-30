<?php

use App\Http\Controllers\ObraController;
use App\Models\Obra;
use App\Models\Imovel;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('obra controller direct calls cover store update destroy branches', function () {
    $controller = new ObraController();

    $imovel = Imovel::factory()->create();

    // store normal
    $reqStore = Request::create('/obras', 'POST', ['descricao' => 'Direct', 'valor' => 100, 'imovel_id' => $imovel->id]);
    $resStore = $controller->store($reqStore);
    expect($resStore)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('obras', ['descricao' => 'Direct']);

    $obra = Obra::first();

    // update normal
    $reqUpdate = Request::create('/obras/' . $obra->id, 'PUT', ['descricao' => 'Direct Updated', 'valor' => 200, 'imovel_id' => $imovel->id]);
    $resUpdate = $controller->update($reqUpdate, $obra);
    expect($resUpdate)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('obras', ['id' => $obra->id, 'descricao' => 'Direct Updated']);

    // destroy normal
    $resDestroy = $controller->destroy($obra);
    expect($resDestroy)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);

    // (DB failure simulation covered in ObraControllerTestPest)
});
