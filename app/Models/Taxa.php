<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Imovel;
use App\Models\Aluguel;
use App\Models\Propriedade;
use Database\Factories\TaxaFactory;

/**
 * Class Taxa
 *
 * @property int|null $imovel_id
 * @property int|null $propriedade_id
 * @property int|null $aluguel_id
 * @property float $valor
 * @property string $pagador
 * @property \App\Models\Imovel|null $imovel
 * @property \App\Models\Aluguel|null $aluguel
 * @property \App\Models\Propriedade|null $propriedade
 * @property int|null $proprietario_id
 *
 * @method static \Database\Factories\TaxaFactory factory(...$parameters)
 *
 * @use HasFactory<\Database\Factories\TaxaFactory>
 */
class Taxa extends Model
{
     /** @use HasFactory<TaxaFactory> */
     use HasFactory;
     protected static string $factory = TaxaFactory::class;
    protected $table = 'taxas';

    protected $fillable = [
        'imovel_id', 'propriedade_id', 'aluguel_id', 'tipo', 'valor', 'data_pagamento', 'pagador', 'observacao', 'proprietario_id'
    ];

    protected $casts = [
        'data_pagamento' => 'date',
        'valor' => 'decimal:2',
    ];

    /**
     * @return BelongsTo<Imovel, $this>
     */
    public function imovel(): BelongsTo
    {
        return $this->belongsTo(Imovel::class);
    }

    /**
     * @return BelongsTo<Aluguel, $this>
     */
    public function aluguel(): BelongsTo
    {
        return $this->belongsTo(Aluguel::class);
    }

    /**
     * @return BelongsTo<Propriedade, $this>
     */
    public function propriedade(): BelongsTo
    {
        return $this->belongsTo(Propriedade::class);
    }
}
