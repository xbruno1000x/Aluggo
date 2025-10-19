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

test('Pagamento::markPaid usa now() quando when eh null', function () {
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

    $p->markPaid(1000.0);
    $p->refresh();
    
    expect($p->status)->toBe('paid');
    expect($p->data_pago)->not()->toBeNull();
    expect($p->data_pago)->toBeInstanceOf(Carbon::class);
});

test('Pagamento::markPaid nao seta observacao quando obs eh null', function () {
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
        'observacao' => 'observacao inicial',
    ]);

    $p->markPaid(1000.0, now(), null);
    $p->refresh();
    
    expect($p->observacao)->toBe('observacao inicial');
});

test('Pagamento tem relacao aluguel que retorna BelongsTo', function () {
    $imovel = Imovel::factory()->create();
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
    ]);

    $p = Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => Carbon::now()->startOfMonth()->toDateString(),
        'valor_devido' => 1000,
    ]);

    $relation = $p->aluguel();
    
    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
    expect($p->aluguel->id)->toBe($aluguel->id);
});

test('Pagamento tem casts corretos para campos', function () {
    $imovel = Imovel::factory()->create();
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
    ]);

    $refDate = Carbon::parse('2025-05-01');
    $p = Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => $refDate->toDateString(),
        'valor_devido' => 1234.56,
        'valor_recebido' => 678.90,
    ]);

    $p->refresh();
    
    expect($p->referencia_mes)->toBeInstanceOf(Carbon::class);
    expect($p->valor_devido)->toBe('1234.56');
    expect($p->valor_recebido)->toBe('678.90');
});
