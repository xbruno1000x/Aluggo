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
    $secret = \Illuminate\Support\Facades\Crypt::decryptString($proprietario->two_factor_secret);
    $code = $google2fa->getCurrentOtp($secret);

    expect($proprietario->verifyTwoFactorCode($code))->toBeTrue();

    $qr = $proprietario->getTwoFactorQRCodeUrl();
    expect($qr)->toBeString()->not->toBeEmpty();
});

test('verifyTwoFactorCode retorna false quando secret é null', function () {
    $proprietario = Proprietario::factory()->create([
        'two_factor_secret' => null,
    ]);

    expect($proprietario->verifyTwoFactorCode('123456'))->toBeFalse();
});

test('verifyTwoFactorCode retorna false quando secret está vazio', function () {
    $proprietario = Proprietario::factory()->create([
        'two_factor_secret' => '',
    ]);

    expect($proprietario->verifyTwoFactorCode('123456'))->toBeFalse();
});

test('verifyTwoFactorCode retorna false com código inválido', function () {
    $proprietario = Proprietario::factory()->create();
    $proprietario->enableTwoFactorAuthentication();

    expect($proprietario->verifyTwoFactorCode('000000'))->toBeFalse();
});

test('getTwoFactorQRCodeUrl retorna string vazia quando secret é null', function () {
    $proprietario = Proprietario::factory()->create([
        'two_factor_secret' => null,
    ]);

    expect($proprietario->getTwoFactorQRCodeUrl())->toBe('');
});

test('getTwoFactorQRCodeUrl retorna string vazia quando secret está vazio', function () {
    $proprietario = Proprietario::factory()->create([
        'two_factor_secret' => '',
    ]);

    expect($proprietario->getTwoFactorQRCodeUrl())->toBe('');
});

test('decryptTwoFactorSecret lida com exceptions de decriptação', function () {
    $proprietario = Proprietario::factory()->create();
    
    $proprietario->two_factor_secret = 'valor_invalido_nao_criptografado';
    $proprietario->save();

    expect($proprietario->verifyTwoFactorCode('123456'))->toBeFalse();
    expect($proprietario->getTwoFactorQRCodeUrl())->toBe('');
});

test('formata telefone celular com 11 digitos', function () {
    $proprietario = Proprietario::factory()->create([
        'telefone' => '11988887777',
    ]);

    expect($proprietario->telefone)->toBe('(11)98888-7777');
});

test('formata telefone fixo com 10 digitos', function () {
    $proprietario = Proprietario::factory()->create([
        'telefone' => '1133334444',
    ]);

    expect($proprietario->telefone)->toBe('(11)3333-4444');
});

test('formata telefone com codigo de pais', function () {
    $proprietario = Proprietario::factory()->create([
        'telefone' => '+5511988887777',
    ]);

    expect($proprietario->telefone)->toBe('+55 (11)98888-7777');
});

test('mantém telefone vazio quando não há digitos', function () {
    $proprietario = Proprietario::factory()->create([
        'telefone' => '',
    ]);

    expect($proprietario->telefone)->toBe('');
});

test('mantém formato original para telefones com formato diferente', function () {
    $proprietario = Proprietario::factory()->create([
        'telefone' => '123',
    ]);

    expect($proprietario->telefone)->toBe('123');
});
