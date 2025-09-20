<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\AluguelFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Aluguel extends Model
{
    /** @use HasFactory<AluguelFactory> */
    use HasFactory;

    protected static string $factory = AluguelFactory::class;

    protected $table = 'alugueis';

    protected $fillable = [
        'valor_mensal',
        'data_inicio',
        'data_fim',
        'imovel_id',
        'locatario_id',
    ];

    /**
     * @return BelongsTo<Imovel, $this>
     */
    public function imovel(): BelongsTo
    {
        return $this->belongsTo(Imovel::class, 'imovel_id');
    }

    /**
     * @return BelongsTo<Locatario, $this>
     */
    public function locatario(): BelongsTo
    {
        return $this->belongsTo(Locatario::class, 'locatario_id');
    }
}