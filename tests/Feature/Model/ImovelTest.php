<?php

use App\Models\Imovel;
use App\Models\Propriedade;
use App\Models\Proprietario;
use App\Models\Transacao;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->proprietario = Proprietario::create([
        'nome' => 'Proprietario Imovel',
        'cpf' => '55566677788',
        'telefone' => '11966666666',
        'email' => 'imovel_owner_' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->propriedade = Propriedade::create([
        'nome' => 'Propriedade Teste',
        'endereco' => 'Av. Teste, 456',
        'descricao' => 'Descricao teste',
        'proprietario_id' => $this->proprietario->id,
    ]);
});

test('imovel pode ser criado com atributos válidos', function () {
    $imovel = Imovel::create([
        'nome' => 'Apartamento 101',
        'tipo' => 'Apartamento',
        'valor_compra' => 250000,
        'status' => 'disponivel',
        'data_aquisicao' => now(),
        'propriedade_id' => $this->propriedade->id,
    ]);

    expect($imovel)->toBeInstanceOf(Imovel::class)
        ->and($imovel->nome)->not->toBeEmpty()
        ->and($imovel->tipo)->not->toBeEmpty()
        ->and($imovel->valor_compra)->toBeGreaterThan(0)
        ->and($imovel->status)->not->toBeEmpty()
        ->and($imovel->propriedade_id)->toBe($this->propriedade->id);
});

test('imovel pertence a uma propriedade e proprietario', function () {
    $imovel = Imovel::create([
        'nome' => 'Apartamento 101',
        'tipo' => 'Apartamento',
        'valor_compra' => 250000,
        'status' => 'disponivel',
        'data_aquisicao' => now(),
        'propriedade_id' => $this->propriedade->id,
    ]);

    expect($imovel->propriedade)->toBeInstanceOf(Propriedade::class)
        ->and($imovel->propriedade->proprietario)->toBeInstanceOf(Proprietario::class)
        ->and($imovel->propriedade->proprietario->id)->toBe($this->proprietario->id);
});

test('imovel factory gera numero', function () {
    $imovel = Imovel::factory()->create(['propriedade_id' => $this->propriedade->id]);

    expect($imovel->numero)->not->toBeEmpty();
});

test('imovel pode ter obras e retorna relacionamentos corretamente', function () {
    $imovel = Imovel::factory()->create(['propriedade_id' => $this->propriedade->id]);

    $obra = \App\Models\Obra::factory()->create([
        'imovel_id' => $imovel->id,
        'descricao' => 'Pequena reforma',
    ]);

    $imovel->refresh();

    expect($imovel->obras)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($imovel->obras->contains($obra))->toBeTrue()
        ->and($imovel->propriedade)->toBeInstanceOf(Propriedade::class);
});

test('imovel pode ter transacoes e retorna relacionamentos corretamente', function () {
    $imovel = Imovel::factory()->create(['propriedade_id' => $this->propriedade->id]);

    $transacao = Transacao::factory()->create([
        'imovel_id' => $imovel->id,
        'valor_venda' => 100000,
    ]);

    $imovel->refresh();

    expect($imovel->transacoes)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($imovel->transacoes->contains($transacao))->toBeTrue();
});

test('metodos de relacao retornam instancias corretas', function () {
    $imovel = Imovel::factory()->make();

    // chamar os métodos de relação para cobrir as linhas e garantir tipos
    $propRel = $imovel->propriedade();
    $obrasRel = $imovel->obras();
    $transRel = $imovel->transacoes();

    expect($propRel)->toBeInstanceOf(BelongsTo::class)
        ->and($obrasRel)->toBeInstanceOf(HasMany::class)
        ->and($transRel)->toBeInstanceOf(HasMany::class);
});