<?php

use App\Models\Propriedade;
use App\Models\Proprietario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->proprietario = Proprietario::create([
        'nome' => 'Proprietario Test',
        'cpf' => '22233344455',
        'telefone' => '11977777777',
        'email' => 'prop_owner_' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
    ]);
});

test('propriedade pode ser criada com atributos válidos', function () {
    $propriedade = Propriedade::create([
        'nome' => 'Imobiliária Central',
        'endereco' => 'Rua Falsa, 123',
        'descricao' => 'Descrição de teste',
        'proprietario_id' => $this->proprietario->id,
    ]);

    expect($propriedade)->toBeInstanceOf(Propriedade::class)
        ->and($propriedade->nome)->not->toBeEmpty()
        ->and($propriedade->endereco)->not->toBeEmpty()
        ->and($propriedade->descricao)->not->toBeEmpty()
        ->and($propriedade->proprietario_id)->toBe($this->proprietario->id);
});

test('propriedade pertence a um proprietario', function () {
    $propriedade = Propriedade::create([
        'nome' => 'Imobiliária Central',
        'endereco' => 'Rua Falsa, 123',
        'descricao' => 'Descrição de teste',
        'proprietario_id' => $this->proprietario->id,
    ]);

    expect($propriedade->proprietario)->toBeInstanceOf(Proprietario::class)
        ->and($propriedade->proprietario->id)->toBe($this->proprietario->id);
});

test('propriedade pode ter varios imoveis e bairro pode ser salvo', function () {
    $propriedade = Propriedade::create([
        'nome' => 'Imobiliária Central',
        'endereco' => 'Rua Falsa, 123',
        'descricao' => 'Descrição de teste',
        'bairro' => 'Centro',
        'proprietario_id' => $this->proprietario->id,
    ]);

    $imovel = \App\Models\Imovel::factory()->create(['propriedade_id' => $propriedade->id]);

    $propriedade->refresh();

    expect($propriedade->imoveis)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class)
        ->and($propriedade->imoveis->contains($imovel))->toBeTrue()
        ->and($propriedade->bairro)->toBe('Centro');
});

test('propriedade pode ser criada com todos os atributos fillable', function () {
    $propriedade = Propriedade::create([
        'nome' => 'Complexo Residencial',
        'endereco' => 'Av. Paulista, 1000',
        'cep' => '01310-100',
        'cidade' => 'São Paulo',
        'estado' => 'SP',
        'bairro' => 'Bela Vista',
        'descricao' => 'Propriedade comercial de alto padrão',
        'proprietario_id' => $this->proprietario->id,
    ]);

    expect($propriedade->cep)->toBe('01310-100')
        ->and($propriedade->cidade)->toBe('São Paulo')
        ->and($propriedade->estado)->toBe('SP');
});

test('propriedade factory usa HasFactory trait', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->proprietario->id]);

    expect($propriedade)->toBeInstanceOf(Propriedade::class)
        ->and($propriedade->id)->toBeGreaterThan(0);
});

test('propriedade tem relacao imoveis que retorna HasMany', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->proprietario->id]);
    
    $relation = $propriedade->imoveis();
    
    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});

test('propriedade tem relacao proprietario que retorna BelongsTo', function () {
    $propriedade = Propriedade::factory()->create(['proprietario_id' => $this->proprietario->id]);
    
    $relation = $propriedade->proprietario();
    
    expect($relation)->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class);
});