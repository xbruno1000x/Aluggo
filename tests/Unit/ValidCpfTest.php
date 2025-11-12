<?php

use App\Rules\ValidCpf;

test('valida CPF válido', function () {
    $rule = new ValidCpf();
    $fail = function ($message) {
        throw new Exception($message);
    };
    
    expect(fn() => $rule->validate('cpf', '12345678909', $fail))->not->toThrow(Exception::class);
});

test('rejeita CPF com menos de 11 dígitos', function () {
    $rule = new ValidCpf();
    $messages = [];
    $fail = function ($message) use (&$messages) {
        $messages[] = $message;
    };
    
    $rule->validate('cpf', '123456789', $fail);
    
    expect($messages)->toHaveCount(1);
    expect($messages[0])->toBe('O CPF deve conter 11 dígitos.');
});

test('rejeita CPF com mais de 11 dígitos', function () {
    $rule = new ValidCpf();
    $messages = [];
    $fail = function ($message) use (&$messages) {
        $messages[] = $message;
    };
    
    $rule->validate('cpf', '123456789012', $fail);
    
    expect($messages)->toHaveCount(1);
    expect($messages[0])->toBe('O CPF deve conter 11 dígitos.');
});

test('rejeita CPF com todos dígitos iguais', function () {
    $cpfsInvalidos = [
        '00000000000',
        '11111111111',
        '22222222222',
        '33333333333',
        '44444444444',
        '55555555555',
        '66666666666',
        '77777777777',
        '88888888888',
        '99999999999',
    ];
    
    $rule = new ValidCpf();
    
    foreach ($cpfsInvalidos as $cpf) {
        $messages = [];
        $fail = function ($message) use (&$messages) {
            $messages[] = $message;
        };
        
        $rule->validate('cpf', $cpf, $fail);
        
        expect($messages)->toHaveCount(1);
        expect($messages[0])->toBe('O CPF informado é inválido.');
    }
});

test('rejeita CPF com dígito verificador inválido', function () {
    $rule = new ValidCpf();
    $messages = [];
    $fail = function ($message) use (&$messages) {
        $messages[] = $message;
    };
    
    // CPF com dígito verificador errado
    $rule->validate('cpf', '12345678900', $fail);
    
    expect($messages)->toHaveCount(1);
    expect($messages[0])->toBe('O CPF informado é inválido.');
});

test('aceita CPF com formatação', function () {
    $rule = new ValidCpf();
    $fail = function ($message) {
        throw new Exception($message);
    };
    
    // CPF válido com formatação: 123.456.789-09
    expect(fn() => $rule->validate('cpf', '123.456.789-09', $fail))->not->toThrow(Exception::class);
});

test('valida CPFs reais conhecidos', function () {
    $cpfsValidos = [
        '11144477735', // CPF válido conhecido
        '52998224725', // CPF válido conhecido
    ];
    
    $rule = new ValidCpf();
    
    foreach ($cpfsValidos as $cpf) {
        $messages = [];
        $fail = function ($message) use (&$messages) {
            $messages[] = $message;
        };
        
        $rule->validate('cpf', $cpf, $fail);
        
        expect($messages)->toHaveCount(0);
    }
});
