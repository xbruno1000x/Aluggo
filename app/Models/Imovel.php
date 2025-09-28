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
     * @return HasMany<Obra>
     */
    public function obras(): HasMany
    {
        return $this->hasMany(Obra::class, 'imovel_id');
    }
}