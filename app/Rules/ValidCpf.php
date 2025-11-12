<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidCpf implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $cpf = preg_replace('/[^0-9]/', '', (string) $value);
        
        if (strlen($cpf) != 11) {
            $fail('O CPF deve conter 11 dígitos.');
            return;
        }
        
        if (preg_match('/(\d)\1{10}/', $cpf)) {
            $fail('O CPF informado é inválido.');
            return;
        }
        
        $sum = 0;
        for ($i = 0; $i < 9; $i++) {
            $sum += ((int) $cpf[$i]) * (10 - $i);
        }
        $remainder = $sum % 11;
        $digit1 = ($remainder < 2) ? 0 : 11 - $remainder;
        
        if (((int) $cpf[9]) !== $digit1) {
            $fail('O CPF informado é inválido.');
            return;
        }
        
        $sum = 0;
        for ($i = 0; $i < 10; $i++) {
            $sum += ((int) $cpf[$i]) * (11 - $i);
        }
        $remainder = $sum % 11;
        $digit2 = ($remainder < 2) ? 0 : 11 - $remainder;
        
        if (((int) $cpf[10]) !== $digit2) {
            $fail('O CPF informado é inválido.');
            return;
        }
    }
}
