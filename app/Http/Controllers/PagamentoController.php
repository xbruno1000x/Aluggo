<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use App\Models\Pagamento;
use App\Models\Aluguel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class PagamentoController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $month = $request->query('month', Carbon::now()->startOfMonth()->toDateString());
        $ref = $this->normalizeMonthToStart($month);
        $refDate = Carbon::parse($ref)->startOfMonth()->toDateString();

        $aluguelFilter = null;
        if ($request->has('aluguel_id')) {
            $aluguelFilter = (int)$request->query('aluguel_id');
        }

        $start = Carbon::parse($ref)->startOfMonth();
        $end = Carbon::parse($ref)->endOfMonth();

                        $alugueisQuery = Aluguel::whereDate('data_inicio', '<=', $end->toDateString())
                            ->where(function ($q) use ($start) {
                                $q->whereNull('data_fim')
                                  ->orWhereDate('data_fim', '>=', $start->toDateString());
                            });

        if ($aluguelFilter) {
            $alugueisQuery->where('id', $aluguelFilter);
        }

        $alugueis = $alugueisQuery->get();
        try {
            $logData = [
                'requested_month_normalized' => $refDate,
                'alugueis_count' => $alugueis->count(),
            ];
            Log::debug('PagamentoController::index alugueis considered', $logData);
        } catch (\Exception $e) {
        }
        $alugueisIds = $alugueis->pluck('id')->all();
        $now = now();
        $inserts = [];
        foreach ($alugueis as $a) {
            $inserts[] = [
                'aluguel_id' => $a->id,
                'referencia_mes' => $refDate,
                'valor_devido' => $a->valor_mensal ?? 0,
                'valor_recebido' => 0,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($inserts)) {
            try {
                DB::table('pagamentos')->insertOrIgnore($inserts);
            } catch (\Exception $e) {
                Log::warning('Pagamento bulk insertOrIgnore failed: ' . $e->getMessage());
            }
        }

        $query = Pagamento::with('aluguel.imovel', 'aluguel.locatario')
            ->whereDate('referencia_mes', $refDate)
            ->whereNotExists(function ($q) use ($refDate) {
                $q->select(DB::raw('1'))
                  ->from('transacoes')
                  ->join('imoveis', 'transacoes.imovel_id', '=', 'imoveis.id')
                  ->join('alugueis', 'alugueis.imovel_id', '=', 'imoveis.id')
                  ->whereColumn('alugueis.id', 'pagamentos.aluguel_id')
                  ->whereDate('transacoes.data_venda', '<=', $refDate);
            });

        if ($aluguelFilter) {
            $query->where('aluguel_id', $aluguelFilter);
        }

        $pagamentos = $query->paginate(20);
        $pagamentos->appends($request->query());

        $ref = $refDate;
        return view('pagamentos.index', compact('pagamentos', 'ref'));
    }

    protected function normalizeMonthToStart(string $input): string
    {
        $input = trim($input);
        if (preg_match('/^\d{2}\/\d{4}$/', $input)) {
            try {
                $dt = Carbon::createFromFormat('m/Y', $input)->startOfMonth();
                return $dt->toDateString();
            } catch (\Exception $e) {
            }
        }

        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $input)) {
            try {
                $dt = Carbon::createFromFormat('d/m/Y', $input)->startOfMonth();
                return $dt->toDateString();
            } catch (\Exception $e) {
            }
        }

        try {
            $dt = Carbon::parse($input)->startOfMonth();
            return $dt->toDateString();
        } catch (\Exception $e) {
            // fallback to now
            return Carbon::now()->startOfMonth()->toDateString();
        }
    }

    public function markPaid(Request $request, Pagamento $pagamento): RedirectResponse
    {
        $data = $request->validate([
            'valor_recebido' => ['required', 'numeric', 'min:0'],
            'observacao' => ['nullable', 'string'],
        ]);

        $pagamento->markPaid((float)$data['valor_recebido'], now(), $data['observacao'] ?? null);

        return redirect()->back()->with('success', 'Pagamento marcado.');
    }

    public function revert(Pagamento $pagamento): RedirectResponse
    {
        $pagamento->valor_recebido = 0;
        $pagamento->status = 'pending';
        $pagamento->data_pago = null;
        $pagamento->observacao = null;
        $pagamento->save();

        return redirect()->back()->with('success', 'Pagamento revertido.');
    }

    public function renew(Aluguel $aluguel): RedirectResponse
    {
        if ($aluguel->data_fim) {
            $start = Carbon::parse($aluguel->data_inicio);
            $end = Carbon::parse($aluguel->data_fim);
            $intervalDays = $start->diffInDays($end);
            $aluguel->data_fim = Carbon::parse($aluguel->data_fim)->addDays($intervalDays);
        } else {
            $aluguel->data_fim = Carbon::parse($aluguel->data_inicio)->addYear();
        }
        $aluguel->save();
        return redirect()->back()->with('success', 'Contrato renovado.');
    }

    public function markAllPaid(Request $request): RedirectResponse
    {
        $month = $request->input('month', Carbon::now()->startOfMonth()->toDateString());
        $ref = $this->normalizeMonthToStart($month);
        $refDate = Carbon::parse($ref)->startOfMonth()->toDateString();

        $aluguelId = $request->input('aluguel_id');

        $start = Carbon::parse($ref)->startOfMonth();
        $end = Carbon::parse($ref)->endOfMonth();

                $alugueisQuery = Aluguel::whereDate('data_inicio', '<=', $end->toDateString())
            ->where(function ($q) use ($start) {
                $q->whereNull('data_fim')
                  ->orWhereDate('data_fim', '>=', $start->toDateString());
            });

        if ($aluguelId) {
            $alugueisQuery->where('id', (int)$aluguelId);
        }

        $alugueis = $alugueisQuery->get();
        $now = now();
        $inserts = [];
        foreach ($alugueis as $a) {
            $inserts[] = [
                'aluguel_id' => $a->id,
                'referencia_mes' => $refDate,
                'valor_devido' => $a->valor_mensal ?? 0,
                'valor_recebido' => 0,
                'status' => 'pending',
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if (!empty($inserts)) {
            try {
                DB::table('pagamentos')->insertOrIgnore($inserts);
            } catch (\Exception $e) {
                Log::warning('Pagamento bulk insertOrIgnore failed (markAllPaid): ' . $e->getMessage());
            }
        }

        $now = now();
        $pagQuery = Pagamento::whereDate('referencia_mes', $refDate)
            ->whereIn('status', ['pending', 'partial'])
            ->whereNotExists(function ($q) use ($refDate) {
                $q->select(DB::raw('1'))
                  ->from('transacoes')
                  ->join('imoveis', 'transacoes.imovel_id', '=', 'imoveis.id')
                  ->join('alugueis', 'alugueis.imovel_id', '=', 'imoveis.id')
                  ->whereColumn('alugueis.id', 'pagamentos.aluguel_id')
                  ->whereDate('transacoes.data_venda', '<=', $refDate);
            });
        if ($aluguelId) $pagQuery->where('aluguel_id', (int)$aluguelId);

        $pagamentos = $pagQuery->get();
        foreach ($pagamentos as $pag) {
            $pag->valor_recebido = $pag->valor_devido;
            $pag->status = 'paid';
            $pag->data_pago = $now;
            $pag->observacao = null;
            $pag->save();
        }

        return redirect()->back()->with('success', 'Todos os pagamentos marcados como pagos.');
    }
}
