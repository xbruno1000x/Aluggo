<?php

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use App\Models\Pagamento;
use App\Models\Proprietario;
use App\Models\Propriedade;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->proprietario = Proprietario::factory()->create();
    $this->actingAs($this->proprietario, 'proprietario');
    
    $this->propriedade = Propriedade::factory()->create(['proprietario_id' => $this->proprietario->id]);
    $this->imovel = Imovel::factory()->create(['propriedade_id' => $this->propriedade->id]);
});

test('lista pagamentos do mes e marca pago', function () {
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
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
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
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
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
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

    $this->post(route('pagamentos.markPaid', $p), ['valor_recebido' => 1000]);
    $p->refresh();
    expect($p->status)->toBe('paid');

    $this->post(route('pagamentos.revert', $p))->assertRedirect();
    $p->refresh();
    expect($p->status)->toBe('pending');
    expect((float)$p->valor_recebido)->toBe(0.0);
    expect($p->data_pago)->toBeNull();
    expect($p->observacao)->toBeNull();
});

test('mark all paid marca todos pagamentos pendentes e parciais', function () {
    
    $imovel1 = Imovel::factory()->for(Propriedade::factory()->for($this->proprietario, 'proprietario'))->create();
    $imovel2 = Imovel::factory()->for(Propriedade::factory()->for($this->proprietario, 'proprietario'))->create();
    $loc1 = Locatario::factory()->create();
    $loc2 = Locatario::factory()->create();

    $al1 = Aluguel::factory()->create([
        'imovel_id' => $imovel1->id,
        'locatario_id' => $loc1->id,
        'valor_mensal' => 800,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
        'data_fim' => null,
    ]);
    $al2 = Aluguel::factory()->create([
        'imovel_id' => $imovel2->id,
        'locatario_id' => $loc2->id,
        'valor_mensal' => 1200,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
        'data_fim' => null,
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();

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
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
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
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
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

test('normalizeMonthToStart trata formato invalido e retorna now', function () {
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 800,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $response = $this->get(route('pagamentos.index', ['month' => 'formato-invalido']));
    $response->assertStatus(200);
    
    $ref = Carbon::now()->startOfMonth()->toDateString();
    $p = Pagamento::where('aluguel_id', $aluguel->id)->where('referencia_mes', $ref)->first();
    expect($p)->not()->toBeNull();
});

test('index filtra por aluguel_id quando fornecido', function () {
    $loc1 = Locatario::factory()->create();
    $loc2 = Locatario::factory()->create();
    
    $imovel2 = Imovel::factory()->create(['propriedade_id' => $this->propriedade->id]);
    
    $aluguel1 = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $loc1->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);
    
    $aluguel2 = Aluguel::factory()->create([
        'imovel_id' => $imovel2->id,
        'locatario_id' => $loc2->id,
        'valor_mensal' => 1500,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();
    
    $response = $this->get(route('pagamentos.index', ['month' => $ref, 'aluguel_id' => $aluguel1->id]));
    $response->assertStatus(200);
    
    $p1 = Pagamento::where('aluguel_id', $aluguel1->id)->where('referencia_mes', $ref)->first();
    expect($p1)->not()->toBeNull();
});

test('markPaid valida campos obrigatorios', function () {
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $p = Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => Carbon::now()->startOfMonth()->toDateString(),
        'valor_devido' => 1000,
    ]);

    $response = $this->post(route('pagamentos.markPaid', $p), []);
    $response->assertSessionHasErrors('valor_recebido');
});

test('markPaid valida valor minimo', function () {
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $p = Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => Carbon::now()->startOfMonth()->toDateString(),
        'valor_devido' => 1000,
    ]);

    $response = $this->post(route('pagamentos.markPaid', $p), ['valor_recebido' => -100]);
    $response->assertSessionHasErrors('valor_recebido');
});

test('markPaid aborta 403 quando pagamento nao pertence ao proprietario', function () {
    $outroProprietario = Proprietario::factory()->create();
    $outraPropriedade = Propriedade::factory()->create(['proprietario_id' => $outroProprietario->id]);
    $outroImovel = Imovel::factory()->create(['propriedade_id' => $outraPropriedade->id]);
    $loc = Locatario::factory()->create();
    $outroAluguel = Aluguel::factory()->create([
        'imovel_id' => $outroImovel->id,
        'locatario_id' => $loc->id,
    ]);

    $p = Pagamento::create([
        'aluguel_id' => $outroAluguel->id,
        'referencia_mes' => Carbon::now()->startOfMonth()->toDateString(),
        'valor_devido' => 1000,
    ]);

    $response = $this->post(route('pagamentos.markPaid', $p), ['valor_recebido' => 1000]);
    $response->assertStatus(403);
});

test('revert aborta 403 quando pagamento nao pertence ao proprietario', function () {
    $outroProprietario = Proprietario::factory()->create();
    $outraPropriedade = Propriedade::factory()->create(['proprietario_id' => $outroProprietario->id]);
    $outroImovel = Imovel::factory()->create(['propriedade_id' => $outraPropriedade->id]);
    $loc = Locatario::factory()->create();
    $outroAluguel = Aluguel::factory()->create([
        'imovel_id' => $outroImovel->id,
        'locatario_id' => $loc->id,
    ]);

    $p = Pagamento::create([
        'aluguel_id' => $outroAluguel->id,
        'referencia_mes' => Carbon::now()->startOfMonth()->toDateString(),
        'valor_devido' => 1000,
        'status' => 'paid',
        'valor_recebido' => 1000,
    ]);

    $response = $this->post(route('pagamentos.revert', $p));
    $response->assertStatus(403);
});

test('renew aborta 403 quando aluguel nao pertence ao proprietario', function () {
    $outroProprietario = Proprietario::factory()->create();
    $outraPropriedade = Propriedade::factory()->create(['proprietario_id' => $outroProprietario->id]);
    $outroImovel = Imovel::factory()->create(['propriedade_id' => $outraPropriedade->id]);
    $loc = Locatario::factory()->create();
    $outroAluguel = Aluguel::factory()->create([
        'imovel_id' => $outroImovel->id,
        'locatario_id' => $loc->id,
    ]);

    $response = $this->post(route('alugueis.renew', $outroAluguel));
    $response->assertStatus(403);
});

test('renew estende contrato com data_fim definida baseado no intervalo original', function () {
    $loc = Locatario::factory()->create();
    $dataInicio = Carbon::now()->subYear();
    $dataFim = Carbon::now()->addMonth();
    
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 1000,
        'data_inicio' => $dataInicio->toDateString(),
        'data_fim' => $dataFim->toDateString(),
    ]);

    $intervalOriginal = $dataInicio->diffInDays($dataFim);
    
    $response = $this->post(route('alugueis.renew', $aluguel));
    $response->assertRedirect();
    
    $aluguel->refresh();
    $novaDataFim = Carbon::parse($aluguel->data_fim);
    
    expect($novaDataFim->greaterThan($dataFim))->toBeTrue();
});

test('renew estende contrato sem data_fim adicionando um ano', function () {
    $loc = Locatario::factory()->create();
    $dataInicio = Carbon::now()->subMonths(6);
    
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 1000,
        'data_inicio' => $dataInicio->toDateString(),
        'data_fim' => null,
    ]);
    
    $response = $this->post(route('alugueis.renew', $aluguel));
    $response->assertRedirect();
    
    $aluguel->refresh();
    expect($aluguel->data_fim)->not()->toBeNull();
    
    $esperado = $dataInicio->copy()->addYear()->toDateString();
    expect($aluguel->data_fim)->toBe($esperado);
});

test('markAllPaid filtra por aluguel_id quando fornecido', function () {
    $loc1 = Locatario::factory()->create();
    $loc2 = Locatario::factory()->create();
    
    $imovel2 = Imovel::factory()->create(['propriedade_id' => $this->propriedade->id]);
    
    $aluguel1 = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $loc1->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);
    
    $aluguel2 = Aluguel::factory()->create([
        'imovel_id' => $imovel2->id,
        'locatario_id' => $loc2->id,
        'valor_mensal' => 1500,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();
    
    $p1 = Pagamento::create([
        'aluguel_id' => $aluguel1->id,
        'referencia_mes' => $ref,
        'valor_devido' => 1000,
        'status' => 'pending',
    ]);
    
    $p2 = Pagamento::create([
        'aluguel_id' => $aluguel2->id,
        'referencia_mes' => $ref,
        'valor_devido' => 1500,
        'status' => 'pending',
    ]);
    
    $response = $this->post(route('pagamentos.markAll'), [
        'month' => $ref,
        'aluguel_id' => $aluguel1->id
    ]);
    $response->assertRedirect();
    
    $p1->refresh();
    $p2->refresh();
    
    expect($p1->status)->toBe('paid');
    expect($p2->status)->toBe('pending'); 
});

test('markAllPaid trata exception do insertOrIgnore graciosamente', function () {
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();
    
    $p = Pagamento::create([
        'aluguel_id' => $aluguel->id,
        'referencia_mes' => $ref,
        'valor_devido' => 1000,
        'status' => 'pending',
    ]);

    $response = $this->post(route('pagamentos.markAll'), ['month' => $ref]);
    $response->assertRedirect();
    
    $p->refresh();
    expect($p->status)->toBe('paid');
});

test('index loga debug informacoes quando possivel', function () {
    $loc = Locatario::factory()->create();
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 800,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();
    
    $response = $this->get(route('pagamentos.index', ['month' => $ref]));
    $response->assertStatus(200);
    
    expect(Pagamento::where('aluguel_id', $aluguel->id)->count())->toBeGreaterThan(0);
});

test('index nao exibe pagamentos de outros proprietarios', function () {
    $outroProprietario = Proprietario::factory()->create();
    $outraPropriedade = Propriedade::factory()->create(['proprietario_id' => $outroProprietario->id]);
    $outroImovel = Imovel::factory()->create(['propriedade_id' => $outraPropriedade->id, 'nome' => 'Imovel do Outro']);
    $outroLocatario = Locatario::factory()->create(['nome' => 'Locatario do Outro']);
    
    $outroAluguel = Aluguel::factory()->create([
        'imovel_id' => $outroImovel->id,
        'locatario_id' => $outroLocatario->id,
        'valor_mensal' => 5000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $ref = Carbon::now()->startOfMonth()->toDateString();
    
    Pagamento::create([
        'aluguel_id' => $outroAluguel->id,
        'referencia_mes' => $ref,
        'valor_devido' => 5000,
        'valor_recebido' => 0,
        'status' => 'pending',
    ]);

    $loc = Locatario::factory()->create(['nome' => 'Meu Locatario']);
    $meuAluguel = Aluguel::factory()->create([
        'imovel_id' => $this->imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 1000,
        'data_inicio' => Carbon::now()->startOfMonth()->toDateString(),
    ]);

    $response = $this->get(route('pagamentos.index', ['month' => $ref]));
    $response->assertStatus(200);
    
    $response->assertDontSee('Imovel do Outro');
    $response->assertDontSee('Locatario do Outro');
    $response->assertDontSee('5.000,00');
    
    $response->assertSee('Meu Locatario');
});
