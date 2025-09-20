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
    ];

    /**
     * @return HasMany<Aluguel, $this>
     */
    public function alugueis(): HasMany
    {
        return $this->hasMany(Aluguel::class, 'locatario_id');
    }
}