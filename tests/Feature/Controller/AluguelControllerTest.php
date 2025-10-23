<?php

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\{post, delete, get, patch};

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutMiddleware(\Illuminate\Auth\Middleware\Authenticate::class);
});

test('store rejeita contratos sobrepostos', function () {
    $imovel = Imovel::factory()->create(['status' => 'disponivel']);
    $loc1 = Locatario::factory()->create();
    $loc2 = Locatario::factory()->create();

    Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc1->id,
        'data_inicio' => '2025-10-22',
        'data_fim' => '2025-10-28',
        'valor_mensal' => 1000,
    ]);

    $payload = [
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc2->id,
        'valor_mensal' => 1100,
        'data_inicio' => '2025-10-24',
        'data_fim' => '2025-10-27',
    ];

    $response = $this->post(route('alugueis.store'), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors('imovel_id');

    $this->assertDatabaseCount('alugueis', 1);
});

test('index retorna view com alugueis', function () {
    Aluguel::factory()->count(3)->create();

    $response = $this->get(route('alugueis.index'));

    $response->assertStatus(200);
    $response->assertViewIs('alugueis.index');
    $response->assertViewHas('alugueis');
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

    \App\Models\Aluguel::deleting(function () {
        throw new \Exception('delete fail');
    });

    delete(route('alugueis.destroy', $aluguel))->assertRedirect(route('alugueis.index'));
    $this->assertTrue(session()->has('error'));
});

test('update rejeita contratos sobrepostos', function () {
    $imovel = Imovel::factory()->create(['status' => 'disponivel']);
    $loc1 = Locatario::factory()->create();
    $loc2 = Locatario::factory()->create();

    Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc1->id,
        'data_inicio' => '2025-10-01',
        'data_fim' => '2025-10-10',
        'valor_mensal' => 1000,
    ]);

    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc2->id,
        'data_inicio' => '2025-11-01',
        'data_fim' => '2025-11-10',
        'valor_mensal' => 1200,
    ]);

    $payload = [
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc2->id,
        'data_inicio' => '2025-10-05',
        'data_fim' => '2025-10-15',
        'valor_mensal' => 1300,
    ];

    $response = patch(route('alugueis.update', $aluguel), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors('imovel_id');

    $aluguel->refresh();
    expect($aluguel->data_inicio->toDateString())->toBe('2025-11-01');
    expect($aluguel->data_fim->toDateString())->toBe('2025-11-10');
    expect((float) $aluguel->valor_mensal)->toBe(1200.0);
});

test('update altera contrato ativo e atualiza status dos imoveis', function () {
    $today = now()->toDateString();
    $future = now()->addMonth()->toDateString();

    $imovelAnterior = Imovel::factory()->create(['status' => 'alugado']);
    $imovelNovo = Imovel::factory()->create(['status' => 'disponivel']);
    $loc = Locatario::factory()->create();

    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovelAnterior->id,
        'locatario_id' => $loc->id,
        'data_inicio' => $today,
        'data_fim' => $future,
        'valor_mensal' => 1500,
    ]);

    $payload = [
        'imovel_id' => $imovelNovo->id,
        'locatario_id' => $loc->id,
        'data_inicio' => $today,
        'data_fim' => $future,
        'valor_mensal' => 1750,
    ];

    patch(route('alugueis.update', $aluguel), $payload)->assertRedirect(route('alugueis.index'));

    $aluguel->refresh();
    expect($aluguel->imovel_id)->toBe($imovelNovo->id);
    expect((float) $aluguel->valor_mensal)->toBe(1750.0);

    $imovelAnterior->refresh();
    expect($imovelAnterior->status)->toBe('disponivel');

    $imovelNovo->refresh();
    expect($imovelNovo->status)->toBe('alugado');
});

test('index redireciona com erro quando filtra por imovel vendido', function () {
    $proprietario = \App\Models\Proprietario::factory()->create();
    $this->actingAs($proprietario, 'proprietario');
    
    $propriedade = \App\Models\Propriedade::factory()->create(['proprietario_id' => $proprietario->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id, 'status' => 'vendido']);

    $response = $this->get(route('alugueis.index', ['imovel_id' => $imovel->id]));

    $response->assertRedirect();
    $response->assertSessionHas('error', 'Não é possível gerenciar contratos de imóveis vendidos.');
});

test('create retorna view com imoveis e locatarios do proprietario', function () {
    $proprietario = \App\Models\Proprietario::factory()->create();
    $this->actingAs($proprietario, 'proprietario');
    
    $propriedade = \App\Models\Propriedade::factory()->create(['proprietario_id' => $proprietario->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $locatario = Locatario::factory()->create();

    $response = $this->get(route('alugueis.create'));

    $response->assertStatus(200);
    $response->assertViewIs('alugueis.create');
    $response->assertViewHas('imoveis');
    $response->assertViewHas('locatarios');
});

test('edit retorna view com aluguel', function () {
    $aluguel = Aluguel::factory()->create();

    $response = $this->get(route('alugueis.edit', $aluguel));

    $response->assertStatus(200);
    $response->assertViewIs('alugueis.edit');
    $response->assertViewHas('aluguel');
    $response->assertViewHas('imoveis');
    $response->assertViewHas('locatarios');
});

test('store valida campos obrigatorios', function () {
    $response = $this->post(route('alugueis.store'), []);

    $response->assertSessionHasErrors(['imovel_id', 'locatario_id', 'valor_mensal', 'data_inicio']);
});

test('update valida campos obrigatorios', function () {
    $aluguel = Aluguel::factory()->create();

    $response = $this->put(route('alugueis.update', $aluguel), []);

    $response->assertSessionHasErrors(['imovel_id', 'locatario_id', 'valor_mensal', 'data_inicio']);
});

test('index filtra alugueis por imovel quando fornecido', function () {
    $proprietario = \App\Models\Proprietario::factory()->create();
    $this->actingAs($proprietario, 'proprietario');
    
    $propriedade = \App\Models\Propriedade::factory()->create(['proprietario_id' => $proprietario->id]);
    $imovel1 = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $imovel2 = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    
    $loc = Locatario::factory()->create();
    Aluguel::factory()->create(['imovel_id' => $imovel1->id, 'locatario_id' => $loc->id]);
    Aluguel::factory()->create(['imovel_id' => $imovel2->id, 'locatario_id' => $loc->id]);

    $response = $this->get(route('alugueis.index', ['imovel_id' => $imovel1->id]));

    $response->assertStatus(200);
});

test('store retorna erro quando ocorre exception durante salvamento', function () {
    $imovel = Imovel::factory()->create(['status' => 'disponivel']);
    $loc = Locatario::factory()->create();

    Aluguel::creating(function () {
        throw new \Exception('Database error');
    });

    $payload = [
        'imovel_id' => $imovel->id,
        'locatario_id' => $loc->id,
        'valor_mensal' => 500,
        'data_inicio' => now()->toDateString(),
        'data_fim' => now()->addDays(10)->toDateString(),
    ];

    $response = $this->post(route('alugueis.store'), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors('general');
    
    Aluguel::flushEventListeners();
});

test('update retorna erro quando ocorre exception durante atualizacao', function () {
    $aluguel = Aluguel::factory()->create();

    Aluguel::updating(function () {
        throw new \Exception('Update error');
    });

    $payload = [
        'imovel_id' => $aluguel->imovel_id,
        'locatario_id' => $aluguel->locatario_id,
        'valor_mensal' => 1000,
        'data_inicio' => now()->toDateString(),
    ];

    $response = $this->put(route('alugueis.update', $aluguel), $payload);

    $response->assertRedirect();
    $response->assertSessionHasErrors('general');
    
    Aluguel::flushEventListeners();
});

test('adjust retorna erro quando modo é inválido', function () {
    $aluguel = Aluguel::factory()->create(['valor_mensal' => 1000]);

    $response = $this->patchJson(route('alugueis.adjust', $aluguel), [
        'mode' => 'invalid_mode',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['message' => 'Modo de reajuste inválido.']);
});

test('adjust modo manual valida valor vazio', function () {
    $aluguel = Aluguel::factory()->create(['valor_mensal' => 1000]);

    $response = $this->patchJson(route('alugueis.adjust', $aluguel), [
        'mode' => 'manual',
        'novo_valor' => '',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['message' => 'Informe um valor válido para o reajuste manual.']);
});

test('adjust modo manual valida valor negativo', function () {
    $aluguel = Aluguel::factory()->create(['valor_mensal' => 1000]);

    $response = $this->patchJson(route('alugueis.adjust', $aluguel), [
        'mode' => 'manual',
        'novo_valor' => '-100',
    ]);

    $response->assertStatus(422);
    $response->assertJson(['message' => 'O valor do aluguel não pode ser negativo.']);
});

test('adjust modo manual preview calcula novo valor', function () {
    $aluguel = Aluguel::factory()->create(['valor_mensal' => 1000]);

    $response = $this->patchJson(route('alugueis.adjust', $aluguel), [
        'mode' => 'manual',
        'novo_valor' => '1500,00',
        'preview' => true,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'ok' => true,
        'preview' => true,
        'mode' => 'manual',
        'new_value' => 1500.0,
        'message' => 'Simulação concluída.',
    ]);

    $aluguel->refresh();
    expect((float) $aluguel->valor_mensal)->toBe(1000.0); // Não deve alterar em preview
});

test('adjust modo manual sem preview atualiza valor', function () {
    $aluguel = Aluguel::factory()->create(['valor_mensal' => 1000]);

    $response = $this->patchJson(route('alugueis.adjust', $aluguel), [
        'mode' => 'manual',
        'novo_valor' => '1.500,00',
        'preview' => false,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'ok' => true,
        'preview' => false,
        'mode' => 'manual',
        'new_value' => 1500.0,
        'message' => 'Aluguel reajustado com sucesso.',
    ]);

    $aluguel->refresh();
    expect((float) $aluguel->valor_mensal)->toBe(1500.0);
});

test('adjust modo igpm preview calcula reajuste com service', function () {
    $mockService = Mockery::mock(\App\Services\IgpmService::class);
    $mockService->shouldReceive('accumulatedPercent')
        ->once()
        ->andReturn([
            'percent' => 5.5,
            'from' => \Carbon\Carbon::parse('2024-10-01'),
            'to' => \Carbon\Carbon::parse('2025-10-01'),
        ]);

    $this->app->instance(\App\Services\IgpmService::class, $mockService);

    $aluguel = Aluguel::factory()->create([
        'valor_mensal' => 1000,
        'data_inicio' => now()->subYear()->toDateString(),
    ]);

    $response = $this->patchJson(route('alugueis.adjust', $aluguel), [
        'mode' => 'igpm',
        'preview' => true,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'ok' => true,
        'preview' => true,
        'mode' => 'igpm',
        'igpm_percent' => 5.5,
        'message' => 'Simulação concluída.',
    ]);

    expect($response->json('new_value'))->toBeGreaterThan(1000);
    
    $aluguel->refresh();
    expect((float) $aluguel->valor_mensal)->toBe(1000.0); // Não altera em preview
});

test('adjust modo igpm sem preview atualiza valor', function () {
    $mockService = Mockery::mock(\App\Services\IgpmService::class);
    $mockService->shouldReceive('accumulatedPercent')
        ->once()
        ->andReturn([
            'percent' => 10.0,
            'from' => \Carbon\Carbon::parse('2024-10-01'),
            'to' => \Carbon\Carbon::parse('2025-10-01'),
        ]);

    $this->app->instance(\App\Services\IgpmService::class, $mockService);

    $aluguel = Aluguel::factory()->create([
        'valor_mensal' => 1000,
        'data_inicio' => now()->subYear()->toDateString(),
    ]);

    $response = $this->patchJson(route('alugueis.adjust', $aluguel), [
        'mode' => 'igpm',
        'preview' => false,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'ok' => true,
        'preview' => false,
        'mode' => 'igpm',
        'igpm_percent' => 10.0,
        'message' => 'Aluguel reajustado com sucesso.',
    ]);

    $aluguel->refresh();
    expect((float) $aluguel->valor_mensal)->toBe(1100.0); // 1000 + 10%
});

test('adjust modo igpm retorna erro quando service falha', function () {
    $mockService = Mockery::mock(\App\Services\IgpmService::class);
    $mockService->shouldReceive('accumulatedPercent')
        ->once()
        ->andThrow(new \Exception('Service unavailable'));

    $this->app->instance(\App\Services\IgpmService::class, $mockService);

    $aluguel = Aluguel::factory()->create([
        'valor_mensal' => 1000,
        'data_inicio' => now()->subYear()->toDateString(),
    ]);

    $response = $this->patchJson(route('alugueis.adjust', $aluguel), [
        'mode' => 'igpm',
    ]);

    $response->assertStatus(502);
    $response->assertJson(['message' => 'Falha ao obter o IGP-M. Tente novamente mais tarde.']);
});

test('adjust modo igpm calcula periodo corretamente com data_fim', function () {
    $mockService = Mockery::mock(\App\Services\IgpmService::class);
    $mockService->shouldReceive('accumulatedPercent')
        ->once()
        ->andReturn([
            'percent' => 5.0,
            'from' => \Carbon\Carbon::parse('2024-10-01'),
            'to' => \Carbon\Carbon::parse('2025-10-01'),
        ]);

    $this->app->instance(\App\Services\IgpmService::class, $mockService);

    $aluguel = Aluguel::factory()->create([
        'valor_mensal' => 1000,
        'data_inicio' => now()->subYear()->toDateString(),
        'data_fim' => now()->addMonth()->toDateString(),
    ]);

    $response = $this->patchJson(route('alugueis.adjust', $aluguel), [
        'mode' => 'igpm',
        'preview' => true,
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'ok',
        'preview',
        'mode',
        'new_value',
        'igpm_percent',
        'period' => ['start', 'end', 'start_br', 'end_br'],
    ]);
});

test('terminate encerra contrato e marca imovel como disponivel', function () {
    $proprietario = \App\Models\Proprietario::factory()->create();
    $propriedade = \App\Models\Propriedade::factory()->create(['proprietario_id' => $proprietario->id]);
    $imovel = Imovel::factory()->create([
        'status' => 'alugado',
        'propriedade_id' => $propriedade->id,
    ]);
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'data_inicio' => now()->subMonths(6),
        'data_fim' => now()->addMonths(6),
    ]);

    $this->actingAs($proprietario, 'proprietario');

    expect($imovel->fresh()->status)->toBe('alugado');
    expect($aluguel->data_fim)->not->toBeNull();

    $response = post(route('alugueis.terminate', $aluguel));

    $response->assertRedirect(route('alugueis.index'));
    $response->assertSessionHas('success');

    $aluguel->refresh();
    $imovel->refresh();

    expect($aluguel->data_fim->toDateString())->toBe(now()->toDateString());
    expect($imovel->status)->toBe('disponivel');
});

test('terminate retorna info se contrato ja esta encerrado', function () {
    $proprietario = \App\Models\Proprietario::factory()->create();
    $propriedade = \App\Models\Propriedade::factory()->create(['proprietario_id' => $proprietario->id]);
    $imovel = Imovel::factory()->create([
        'status' => 'disponivel',
        'propriedade_id' => $propriedade->id,
    ]);
    $aluguel = Aluguel::factory()->create([
        'imovel_id' => $imovel->id,
        'data_inicio' => now()->subMonths(12),
        'data_fim' => now()->subMonth(),
    ]);

    $this->actingAs($proprietario, 'proprietario');

    $response = post(route('alugueis.terminate', $aluguel));

    $response->assertRedirect(route('alugueis.index'));
    $response->assertSessionHas('info');
});

test('terminate bloqueia acesso nao autorizado', function () {
    $proprietario = \App\Models\Proprietario::factory()->create();
    $propriedade = \App\Models\Propriedade::factory()->create(['proprietario_id' => $proprietario->id]);
    $imovel = Imovel::factory()->create(['propriedade_id' => $propriedade->id]);
    $aluguel = Aluguel::factory()->create(['imovel_id' => $imovel->id]);

    $outroProprietario = \App\Models\Proprietario::factory()->create();
    $this->actingAs($outroProprietario, 'proprietario');

    $response = post(route('alugueis.terminate', $aluguel));

    $response->assertStatus(403);
});
