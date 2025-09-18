<?php

use App\Models\Proprietario;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('proprietario pode ser criado com atributos válidos', function () {
    $proprietario = Proprietario::create([
        'nome' => 'Teste Proprietario',
        'cpf' => '12345678901',
        'telefone' => '11999999999',
        'email' => 'test_proprietario_' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
    ]);

    expect($proprietario)->toBeInstanceOf(Proprietario::class)
        ->and($proprietario->nome)->not->toBeEmpty()
        ->and($proprietario->email)->not->toBeEmpty()
        ->and($proprietario->cpf)->toHaveLength(11);
});

test('proprietario pode habilitar e desabilitar 2FA', function () {
    $proprietario = Proprietario::create([
        'nome' => 'Teste 2FA',
        'cpf' => '10987654321',
        'telefone' => '11988888888',
        'email' => 'test_2fa_' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
    ]);
    
    $proprietario->enableTwoFactorAuthentication();
    expect($proprietario->two_factor_secret)->not->toBeNull();

    $proprietario->disableTwoFactorAuthentication();
    expect($proprietario->two_factor_secret)->toBeNull();
});