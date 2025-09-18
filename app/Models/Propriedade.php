<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \App\Models\Proprietario;
use \App\Models\Imovel;

class Propriedade extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'nome',
        'endereco',
        'descricao',
        'proprietario_id',
    ];

    /**
     * @return BelongsTo<Proprietario, $this>
     */
    public function proprietario(): BelongsTo
    {
        return $this->belongsTo(Proprietario::class);
    }

    /**
     * @return HasMany<Imovel, $this>
     */
    public function imoveis(): HasMany
    {
        return $this->hasMany(Imovel::class);
    }
}