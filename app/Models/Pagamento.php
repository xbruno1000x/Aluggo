<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Carbon\Carbon;
use App\Models\Aluguel;

/**
 * @property Carbon|null $data_pago
 */
class Pagamento extends Model
{
    protected $table = 'pagamentos';

    protected $fillable = [
        'aluguel_id', 'referencia_mes', 'valor_devido', 'valor_recebido', 'status', 'data_pago', 'observacao'
    ];

    protected $casts = [
        'referencia_mes' => 'date',
        'data_pago' => 'datetime',
        'valor_devido' => 'decimal:2',
        'valor_recebido' => 'decimal:2',
    ];

    /**
     * Relação com aluguel.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Aluguel, \App\Models\Pagamento>
     * @phpstan-return \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Aluguel, \App\Models\Pagamento>
     */
    public function aluguel(): BelongsTo
    {
        /** @var \Illuminate\Database\Eloquent\Relations\BelongsTo<\App\Models\Aluguel, \App\Models\Pagamento> $rel */
        $rel = $this->belongsTo(Aluguel::class, 'aluguel_id');
        return $rel;
    }


    public function markPaid(float $valor, ?\DateTimeInterface $when = null, ?string $obs = null): void
    {
        $this->valor_recebido = $valor;
        $this->status = $valor >= $this->valor_devido ? 'paid' : 'partial';
        if ($when instanceof \DateTimeInterface) {
            $this->data_pago = Carbon::instance($when);
        } else {
            $this->data_pago = now();
        }
        if ($obs) {
            $this->observacao = $obs;
        }
        $this->save();
    }
}
