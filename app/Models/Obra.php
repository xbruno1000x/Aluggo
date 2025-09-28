<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\ObraFactory;

class Obra extends Model
{
    use HasFactory;

    protected static string $factory = ObraFactory::class;

    protected $table = 'obras';

    protected $fillable = [
        'descricao',
        'valor',
        'data_inicio',
        'data_fim',
        'imovel_id',
    ];

    /**
     * @return BelongsTo<Imovel, $this>
     */
    public function imovel(): BelongsTo
    {
        return $this->belongsTo(Imovel::class, 'imovel_id');
    }
}
