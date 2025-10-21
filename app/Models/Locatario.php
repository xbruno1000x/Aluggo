<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\LocatarioFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Locatario extends Model
{
    /** @use HasFactory<LocatarioFactory> */
    use HasFactory;

    protected static string $factory = LocatarioFactory::class;

    protected $table = 'locatarios';

    protected $fillable = [
        'nome',
        'telefone',
        'email',
        'proprietario_id',
    ];

    /**
     * @return HasMany<Aluguel, $this>
     */
    public function alugueis(): HasMany
    {
        return $this->hasMany(Aluguel::class, 'locatario_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<Proprietario, $this>
     */
    public function proprietario(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Proprietario::class, 'proprietario_id');
    }

    /**
     * Accessor para formatar telefone ao recuperar o atributo
     * Ex: +5511988887777 -> +55 (11) 98887-7777
     */
    public function getTelefoneAttribute(?string $value): ?string
    {
        if (empty($value)) return '';

        // Remove tudo que não for número ou +
        $raw = preg_replace('/[^0-9+]/', '', $value);

        // Normalize: se começa com +, keep, else ensure only digits
        $hasPlus = str_starts_with($raw, '+');
        if ($hasPlus) {
            // remove o + para processar números
            $digits = preg_replace('/\D/', '', substr($raw, 1));
        } else {
            $digits = preg_replace('/\D/', '', $raw);
        }

        // Se possuir código de país 55 no início, remova para formatar DDD+numero
        if (strlen($digits) > 10 && substr($digits, 0, 2) === '55') {
            $digits = substr($digits, 2);
            $prefix = '+55 ';
        } else {
            $prefix = $hasPlus ? '+' : '';
        }

        // Agora digits deve ter 10 ou 11 caracteres (DDD + número)
        $len = strlen($digits);
        if ($len === 11) {
            $area = substr($digits, 0, 2);
            $part1 = substr($digits, 2, 5);
            $part2 = substr($digits, 7, 4);
            return ($prefix === '+55 ' ? $prefix : '') . "({$area}){$part1}-{$part2}";
        }

        if ($len === 10) {
            $area = substr($digits, 0, 2);
            $part1 = substr($digits, 2, 4);
            $part2 = substr($digits, 6, 4);
            return ($prefix === '+55 ' ? $prefix : '') . "({$area}){$part1}-{$part2}";
        }

        // Fallback: exiba com prefixo se existir
        return ($prefix === '+55 ' ? $prefix : '') . $digits;
    }
}