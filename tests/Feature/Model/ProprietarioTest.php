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

test('proprietario gera qr code e verifica codigo 2fa corretamente', function () {
    $proprietario = Proprietario::create([
        'nome' => 'Teste 2FA Verifica',
        'cpf' => '20987654321',
        'telefone' => '11977777777',
        'email' => 'test_2fa_verify_' . uniqid() . '@example.com',
        'password' => bcrypt('password123'),
    ]);

    $proprietario->enableTwoFactorAuthentication();
    $proprietario->refresh();

    $google2fa = new \PragmaRX\Google2FA\Google2FA();
    $secret = decrypt($proprietario->two_factor_secret);
    $code = $google2fa->getCurrentOtp($secret);

    expect($proprietario->verifyTwoFactorCode($code))->toBeTrue();

    $qr = $proprietario->getTwoFactorQRCodeUrl();
    expect($qr)->toBeString()->not->toBeEmpty();
});