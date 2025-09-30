<?php

use App\Http\Controllers\PropriedadeController;
use App\Models\Propriedade;
use App\Models\Proprietario;
use Illuminate\Http\Request;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('controlador propriedade: cobre editar, atualizar, excluir e autorização do proprietário', function () {
    /** @var \App\Models\Proprietario $user */
    $user = Proprietario::factory()->create();
    $controller = new PropriedadeController();
    \Illuminate\Support\Facades\Auth::guard('proprietario')->login($user);

    $prop = Propriedade::factory()->create(['proprietario_id' => $user->id]);

    // editar (funciona quando é o proprietário)
    $resEdit = $controller->edit($prop->id);
    expect($resEdit)->toBeInstanceOf(Illuminate\View\View::class);

    // atualizar (atualização)
    $reqUpdate = Request::create('/propriedades/' . $prop->id, 'PUT', ['nome' => 'X', 'endereco' => 'Y', 'bairro' => 'Z']);
    $resUpdate = $controller->update($reqUpdate, $prop->id);
    expect($resUpdate)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);

    // destruir (exclusão)
    $resDestroy = $controller->destroy($prop->id);
    expect($resDestroy)->toBeInstanceOf(Illuminate\Http\RedirectResponse::class);
});
