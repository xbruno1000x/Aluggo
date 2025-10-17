<?php

use App\Models\Pagamento;
use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use App\Models\Proprietario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->proprietario = Proprietario::factory()->create();
});

test('Pagamento::markPaid acumula valor e atualiza status corretamente via model', function () {
    $imovel = Imovel::factory()->create();
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $p = Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => Carbon::now()->startOfMonth()->toDateString(),
        'valor_devido' => 1000,
    ]);

    $p->markPaid(300.0, now(), 'primeira');
    $p->refresh();
    expect($p->status)->toBe('partial');
    expect((float)$p->valor_recebido)->toBe(300.0);

    $p->markPaid(700.0, now(), 'completa');
    $p->refresh();
    expect((float)$p->valor_recebido)->toBe(700.0);
    expect($p->status)->toBe('partial');
});
