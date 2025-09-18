<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \App\Models\Propriedade;

class Imovel extends Model
{
    use HasFactory;
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
}