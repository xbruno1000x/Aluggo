<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class StrongPassword implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            $fail('A senha deve ser uma string válida.');
            return;
        }

        $errors = [];

        if (strlen($value) < 8) {
            $errors[] = 'mínimo de 8 caracteres';
        }

        if (!preg_match('/[A-Z]/', $value)) {
            $errors[] = 'pelo menos uma letra maiúscula';
        }

        if (!preg_match('/[a-z]/', $value)) {
            $errors[] = 'pelo menos uma letra minúscula';
        }

        if (!preg_match('/[0-9]/', $value)) {
            $errors[] = 'pelo menos um número';
        }

        if (!preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $value)) {
            $errors[] = 'pelo menos um símbolo especial (!@#$%^&* etc)';
        }

        if (!empty($errors)) {
            $fail('A senha deve conter: ' . implode(', ', $errors) . '.');
        }
    }
}
