<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\TransacaoFactory;

class Transacao extends Model
{
    /** @use HasFactory<TransacaoFactory> */
    use HasFactory;

    protected static string $factory = TransacaoFactory::class;

    protected $table = 'transacoes';

    protected $fillable = [
        'valor_venda',
        'data_venda',
        'imovel_id',
    ];

    protected $casts = [
        'data_venda' => 'date',
        'valor_venda' => 'decimal:2',
    ];

    /**
     * Relação inversa para o imóvel.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Imovel, $this>
     */
    public function imovel(): BelongsTo
    {
        return $this->belongsTo(Imovel::class, 'imovel_id');
    }
}
