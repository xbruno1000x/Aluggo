<?php

use App\Models\Imovel;
use App\Models\Propriedade;
use App\Models\Proprietario;
use function Pest\Laravel\{actingAs, get, post};

beforeEach(fn () => $this->user = Proprietario::factory()->create());

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