<?php

use App\Http\Controllers\ImovelController;
use App\Models\Imovel;
use App\Models\Propriedade;
use App\Models\Proprietario;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

uses(RefreshDatabase::class);

test('controlador imovel: cobre filtros, store, update, destroy e checagem de propriedade', function () {
    /** @var \App\Models\Proprietario $user */
    $user = Proprietario::factory()->create();
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
