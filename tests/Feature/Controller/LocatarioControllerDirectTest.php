<?php

use App\Http\Controllers\LocatarioController;
use App\Models\Locatario;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('locatario controller direct calls cover index create store edit update destroy', function () {
    $controller = new LocatarioController();

    // index without search
    Locatario::factory()->count(2)->create();
    $req = Request::create('/locatarios', 'GET', []);
    $res = $controller->index($req);
    expect($res)->toBeInstanceOf(Illuminate\View\View::class);

    // index with search
    $req2 = Request::create('/locatarios', 'GET', ['search' => 'nome']);
    $res2 = $controller->index($req2);
    expect($res2)->toBeInstanceOf(Illuminate\View\View::class);

    // create
    $res3 = $controller->create();
    expect($res3)->toBeInstanceOf(Illuminate\View\View::class);

    // store
    $payload = ['nome' => 'Direct Loc', 'telefone' => '119', 'email' => 'direct@example.com'];
    $reqPost = Request::create('/locatarios', 'POST', $payload);
    $resPost = $controller->store($reqPost);
    expect($resPost)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('locatarios', ['nome' => 'Direct Loc']);

    $loc = Locatario::first();

    // edit
    $resEdit = $controller->edit($loc);
    expect($resEdit)->toBeInstanceOf(Illuminate\View\View::class);

    // update
    $reqPut = Request::create('/locatarios/' . $loc->id, 'PUT', ['nome' => 'Alterado', 'telefone' => $loc->telefone, 'email' => $loc->email]);
    $resPut = $controller->update($reqPut, $loc);
    expect($resPut)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseHas('locatarios', ['id' => $loc->id, 'nome' => 'Alterado']);

    // destroy
    $resDel = $controller->destroy($loc);
    expect($resDel)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
    $this->assertDatabaseMissing('locatarios', ['id' => $loc->id]);
});
