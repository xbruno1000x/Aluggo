<?php

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{post, delete};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
});

test('store cria aluguel e atualiza status do imovel quando ativo hoje', function () {
    $imovel = Imovel::factory()->create(['status' => 'disponivel']);
    $loc = Locatario::factory()->create();

    $payload = [
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 500,
        'data_inicio' => now()->toDateString(),
        'data_fim' => now()->addDays(10)->toDateString(),
    ];

    post(route('alugueis.store'), $payload)->assertRedirect(route('alugueis.index'));

    $imovel->refresh();
    expect($imovel->status)->toBe('alugado');
    $this->assertDatabaseHas('alugueis', ['imovel_id' => $imovel->id]);
});

test('destroy exclui aluguel e atualiza imovel para disponivel quando não há outros ativos', function () {
    $imovel = Imovel::factory()->create(['status' => 'alugado']);
    $loc = Locatario::factory()->create();

    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
        'data_inicio' => now()->subDays(1)->toDateString(),
        'data_fim' => now()->addDays(1)->toDateString(),
    ]);

    delete(route('alugueis.destroy', $aluguel))->assertRedirect(route('alugueis.index'));

    $imovel->refresh();
    expect($imovel->status)->toBe('disponivel');
});

test('destroy fallback quando não é removido e ainda existe retorna erro', function () {
    $imovel = Imovel::factory()->create(['status' => 'alugado']);
    $loc = Locatario::factory()->create();

    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
        'data_inicio' => now()->subDays(1)->toDateString(),
        'data_fim' => now()->addDays(1)->toDateString(),
    ]);

    $aluguelId = $aluguel->id;

    // simulate a deletion failure by throwing in the deleting model event
    \App\Models\Aluguel::deleting(function () {
        throw new \Exception('delete fail');
    });

    delete(route('alugueis.destroy', $aluguel))->assertRedirect(route('alugueis.index'));
    $this->assertTrue(session()->has('error'));
});
