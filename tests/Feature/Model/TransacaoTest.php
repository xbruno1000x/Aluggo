<?php

use App\Models\Transacao;
use App\Models\Imovel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

test('factory cria transacao e persiste no banco', function () {
    $transacao = Transacao::factory()->create();

    $this->assertDatabaseHas('transacoes', [
        'id' => $transacao->id,
        'imovel_id' => $transacao->imovel_id,
    ]);
});

test('transacao pertence a um imovel (relação)', function () {
    $imovel = Imovel::factory()->create();
    $transacao = Transacao::factory()->create(['imovel_id' => $imovel->id]);

    expect($transacao->imovel)->toBeInstanceOf(Imovel::class);
    expect($transacao->imovel->id)->toBe($imovel->id);
});

test('data_venda é cast para Carbon e valor_venda tem formato decimal', function () {
    $date = Carbon::create(2025, 9, 30);
    $transacao = Transacao::factory()->create([
        'data_venda' => $date->toDateString(),
        'valor_venda' => 123456.78,
    ]);

    expect($transacao->data_venda)->toBeInstanceOf(Carbon::class);
    expect($transacao->data_venda->toDateString())->toBe($date->toDateString());

    expect(is_numeric($transacao->valor_venda))->toBeTrue();
    expect(number_format((float) $transacao->valor_venda, 2, '.', ''))->toBe(number_format(123456.78, 2, '.', ''));
});
