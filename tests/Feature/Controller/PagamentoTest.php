<?php

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use App\Models\Pagamento;
use App\Models\Proprietario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->proprietario = Proprietario::factory()->create();
    $this->actingAs($this->proprietario, 'proprietario');
});

test('lista pagamentos do mes e marca pago', function () {
    $imovel = Imovel::factory()->create();
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
        'data_fim' => Carbon::now()->addMonths(6)->toDateString(),
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();

    $p = Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => $ref,
        'valor_devido' => 1000,
    ]);

    $this->get(route('pagamentos.index', ['month' => $ref]))->assertStatus(200)->assertSee('Pagamentos');

    $this->post(route('pagamentos.markPaid', $p), ['valor_recebido' => 1000])
        ->assertRedirect();

    $p->refresh();
    $this->assertEquals('paid', $p->status);
});

test('marca pagamento parcial e armazena observacao e data', function () {
    $imovel = Imovel::factory()->create();
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
        'data_fim' => Carbon::now()->addMonths(6)->toDateString(),
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();

    $p = Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => $ref,
        'valor_devido' => 1000,
    ]);

    $this->post(route('pagamentos.markPaid', $p), ['valor_recebido' => 400, 'observacao' => 'parte']);

    $p->refresh();
    expect($p->status)->toBe('partial');
    expect((float)$p->valor_recebido)->toBe(400.0);
    expect($p->observacao)->toBe('parte');
    expect($p->data_pago)->not()->toBeNull();
});

test('reverte pagamento para pendente e limpa campos', function () {
    $imovel = Imovel::factory()->create();
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
        'data_fim' => Carbon::now()->addMonths(6)->toDateString(),
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();

    $p = Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => $ref,
        'valor_devido' => 1000,
    ]);

    // marcar pago totalmente
    $this->post(route('pagamentos.markPaid', $p), ['valor_recebido' => 1000]);
    $p->refresh();
    expect($p->status)->toBe('paid');

    // reverter
    $this->post(route('pagamentos.revert', $p))->assertRedirect();
    $p->refresh();
    expect($p->status)->toBe('pending');
    expect((float)$p->valor_recebido)->toBe(0.0);
    expect($p->data_pago)->toBeNull();
    expect($p->observacao)->toBeNull();
});

test('mark all paid marca todos pagamentos pendentes e parciais', function () {
    $imovel1 = Imovel::factory()->create();
    $imovel2 = Imovel::factory()->create();
    $loc1 = Locatario::factory()->create();
    $loc2 = Locatario::factory()->create();

    $al1 = Aluguel::factory()->create([
        'imovel_id' => $imovel1->id,
        'locatario_id' => $loc1->id,
        'valor_mensal' => 800,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);
    $al2 = Aluguel::factory()->create([
        'imovel_id' => $imovel2->id,
        'locatario_id' => $loc2->id,
        'valor_mensal' => 1200,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();

    // no pagamentos exist initially; markAll should create and mark paid
    $this->post(route('pagamentos.markAll'), ['month' => $ref])->assertRedirect();

    $p1 = Pagamento::where('aluguel_id', $al1->id)->where('referencia_mes', $ref)->first();
    $p2 = Pagamento::where('aluguel_id', $al2->id)->where('referencia_mes', $ref)->first();

    expect($p1)->not()->toBeNull();
    expect($p2)->not()->toBeNull();
    expect($p1->status)->toBe('paid');
    expect((float)$p1->valor_recebido)->toBe((float)$p1->valor_devido);
    expect($p2->status)->toBe('paid');
    expect((float)$p2->valor_recebido)->toBe((float)$p2->valor_devido);
});

test('index cria pagamentos preguiçosamente para alugueis ativos', function () {
    $imovel = Imovel::factory()->create();
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 700,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();

    $this->get(route('pagamentos.index', ['month' => $ref]))->assertStatus(200);

    $p = Pagamento::where('aluguel_id', $aluguel->id)->where('referencia_mes', $ref)->first();
    expect($p)->not()->toBeNull();
    expect($p->status)->toBe('pending');
});

test('aceita formatos de mês MM/YYYY e DD/MM/YYYY no index', function () {
    $imovel = Imovel::factory()->create();
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 900,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $m = Carbon::now()->format('m/Y');
    $this->get(route('pagamentos.index', ['month' => $m]))->assertStatus(200);

    $d = Carbon::now()->format('d/m/Y');
    $this->get(route('pagamentos.index', ['month' => $d]))->assertStatus(200);

    $ref = Carbon::now()->startOfMonth()->toDateString();
    $p = Pagamento::where('aluguel_id', $aluguel->id)->where('referencia_mes', $ref)->first();
    expect($p)->not()->toBeNull();
});
