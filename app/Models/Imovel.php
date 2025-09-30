<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Database\Factories\ImovelFactory;

class Imovel extends Model
{
    /** @use HasFactory<ImovelFactory> */    
    use HasFactory;

    protected static string $factory = ImovelFactory::class;

    protected $table = 'imoveis';

    protected $fillable = [
        'nome',
        'numero',
        'tipo',
        'valor_compra',
        'status',
        'data_aquisicao',
        'propriedade_id',
    ];

    /**
     * @return BelongsTo<Propriedade, $this>
     */
    public function propriedade(): BelongsTo
    {
        return $this->belongsTo(Propriedade::class);
    }

    /**
     * Relação 1:N para obras no imóvel.
     *
     * @return HasMany<Obra, $this>
     */
    public function obras(): HasMany
    {
        return $this->hasMany(Obra::class, 'imovel_id');
    }

    /**
     * Relação 1:N para transações de venda do imóvel.
     *
     * @return HasMany<\App\Models\Transacao, $this>
     */
    public function transacoes(): HasMany
    {
        return $this->hasMany(\App\Models\Transacao::class, 'imovel_id');
    }
}