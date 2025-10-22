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
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PagamentoController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $proprietarioId = Auth::id();

        $month = $request->query('month', Carbon::now()->startOfMonth()->toDateString());
        $ref = $this->normalizeMonthToStart($month);
        $refDate = Carbon::parse($ref)->startOfMonth()->toDateString();

        $aluguelFilter = null;
        if ($request->has('aluguel_id')) {
            $aluguelFilter = (int)$request->query('aluguel_id');
        }

        $start = Carbon::parse($ref)->startOfMonth();
        $end = Carbon::parse($ref)->endOfMonth();

        $alugueisQuery = Aluguel::where(function ($q) use ($start) {
                $q->whereDate('data_inicio', '<', $start->toDateString());
            })
            ->where(function ($q) use ($start) {
                $q->whereNull('data_fim')
                  ->orWhereDate('data_fim', '>=', $start->toDateString());
            })
            ->whereHas('imovel.propriedade', function ($q) use ($proprietarioId) {
                $q->where('proprietario_id', $proprietarioId);
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

        foreach ($alugueis as $a) {
            $valorMensal = $a->valor_mensal ?? 0;

            $refMonthStart = Carbon::parse($refDate)->startOfMonth();
            $refMonthEnd = Carbon::parse($refDate)->endOfMonth();

            $contratoInicio = Carbon::parse($a->data_inicio);
            $contratoFim = $a->data_fim ? Carbon::parse($a->data_fim) : null;

            $lastDayOfRefMonth = $refMonthEnd->day;
            $vencimentoBase = $contratoInicio->copy()
                ->setYear($refMonthStart->year)
                ->setMonth($refMonthStart->month)
                ->day(min($contratoInicio->day, $lastDayOfRefMonth));

            while ($vencimentoBase->lt($refMonthStart)) {
                $vencimentoBase->addMonth();
                $vencimentoBase->day(min($contratoInicio->day, $vencimentoBase->copy()->endOfMonth()->day));
            }

            $periodStart = $vencimentoBase->copy()->subDays(30);
            $periodEnd = $vencimentoBase->copy()->subDay();

            if ($periodStart->lt($contratoInicio)) {
                $periodStart = $contratoInicio->copy();
            }

            $cobrancaFim = $periodEnd->copy();
            $isParcial = false;

            if ($contratoFim && $contratoFim->lessThanOrEqualTo($periodEnd)) {
                $cobrancaFim = $contratoFim->copy();
                $isParcial = true;
            }

            if ($cobrancaFim->lessThan($periodStart)) {
                continue;
            }

            $diasNoPeriodo = $periodStart->diffInDays($cobrancaFim) + 1;
            $valorDevidoBase = $valorMensal;

            if ($isParcial || $diasNoPeriodo < 30) {
                $valorDevidoBase = round(($valorMensal / 30) * $diasNoPeriodo, 2);
            }

            try {
                DB::table('pagamentos')->updateOrInsert(
                    [
                        'aluguel_id' => $a->id,
                        'referencia_mes' => $refDate,
                    ],
                    [
                        'valor_devido' => $valorDevidoBase,
                        'valor_recebido' => DB::raw('COALESCE(valor_recebido, 0)'),
                        'status' => DB::raw("COALESCE(status, 'pending')"),
                        'updated_at' => $now,
                        'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('Pagamento updateOrInsert failed: ' . $e->getMessage());
            }

            if ($contratoFim && $contratoFim->between($refMonthStart, $refMonthEnd) && $contratoFim->greaterThan($periodEnd)) {
                $parcialInicio = $vencimentoBase->copy()->addDay();

                if ($parcialInicio->greaterThan($contratoFim)) {
                    continue;
                }

                $diasParcial = $parcialInicio->diffInDays($contratoFim) + 1;
                $valorParcial = round(($valorMensal / 30) * $diasParcial, 2);
                $referenciaParcial = $contratoFim->toDateString();

                try {
                    DB::table('pagamentos')->updateOrInsert(
                        [
                            'aluguel_id' => $a->id,
                            'referencia_mes' => $referenciaParcial,
                        ],
                        [
                            'valor_devido' => $valorParcial,
                            'valor_recebido' => DB::raw('COALESCE(valor_recebido, 0)'),
                            'status' => DB::raw("COALESCE(status, 'pending')"),
                            'updated_at' => $now,
                            'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                        ]
                    );
                } catch (\Exception $e) {
                    Log::warning('Pagamento parcial updateOrInsert failed: ' . $e->getMessage());
                }
            }
        }

        $query = Pagamento::with('aluguel.imovel', 'aluguel.locatario')
            ->whereBetween('referencia_mes', [$start->toDateString(), $end->toDateString()])
            ->whereHas('aluguel.imovel.propriedade', function ($q) use ($proprietarioId) {
                $q->where('proprietario_id', $proprietarioId);
            })
            ->whereHas('aluguel', function ($q) use ($start) {
                $q->where(function ($sq) use ($start) {
                    $sq->whereNull('data_fim')
                       ->orWhereDate('data_fim', '>=', $start->toDateString());
                });
            })
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

    $pagamentos = $query->orderBy('referencia_mes')->paginate(20);
        $pagamentos->appends($request->query());

        $overdueQuery = Pagamento::with('aluguel.locatario')
            ->whereDate('referencia_mes', '<', $refDate)
            ->whereIn('status', ['pending', 'partial'])
            ->whereHas('aluguel.imovel.propriedade', function ($q) use ($proprietarioId) {
                $q->where('proprietario_id', $proprietarioId);
            })
            ->orderBy('referencia_mes', 'asc');

        $overdueList = $overdueQuery->get();

        $overdues = [];
        foreach ($overdueList as $op) {
            if (!$op->aluguel || !$op->aluguel->locatario) continue;

            try {
                $refMonth = Carbon::parse($op->referencia_mes)->startOfMonth();
                if (!empty($op->aluguel->data_inicio)) {
                    $startDay = Carbon::parse($op->aluguel->data_inicio)->day;
                    $lastDay = $refMonth->copy()->endOfMonth()->day;
                    $day = min($startDay, $lastDay);
                    $dueDate = $refMonth->copy()->day($day);
                } else {
                    $dueDate = $refMonth->copy()->endOfMonth();
                }
            } catch (\Exception $e) {
                continue;
            }

            if (!$dueDate->lt(Carbon::today())) {
                continue;
            }

            $locId = $op->aluguel->locatario->id;
            $monthLabel = Carbon::parse($op->referencia_mes)->format('m/Y');
            if (!isset($overdues[$locId])) {
                $overdues[$locId] = [
                    'locatario' => $op->aluguel->locatario,
                    'months' => [],
                ];
            }
            if (!in_array($monthLabel, $overdues[$locId]['months'])) {
                $overdues[$locId]['months'][] = $monthLabel;
            }
        }

        $ref = $refDate;
        return view('pagamentos.index', compact('pagamentos', 'ref', 'overdues'));
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
            return Carbon::now()->startOfMonth()->toDateString();
        }
    }

    public function markPaid(Request $request, Pagamento $pagamento): RedirectResponse
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$pagamento->aluguel || 
            !$pagamento->aluguel->imovel || 
            !$pagamento->aluguel->imovel->propriedade || 
            $pagamento->aluguel->imovel->propriedade->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }
        
        $data = $request->validate([
            'valor_recebido' => ['required', 'numeric', 'min:0'],
            'observacao' => ['nullable', 'string'],
        ]);

        $pagamento->markPaid((float)$data['valor_recebido'], now(), $data['observacao'] ?? null);

        return redirect()->back()->with('success', 'Pagamento marcado.');
    }

    public function revert(Pagamento $pagamento): RedirectResponse
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$pagamento->aluguel || 
            !$pagamento->aluguel->imovel || 
            !$pagamento->aluguel->imovel->propriedade || 
            $pagamento->aluguel->imovel->propriedade->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }
        
        $pagamento->valor_recebido = 0;
        $pagamento->status = 'pending';
        $pagamento->data_pago = null;
        $pagamento->observacao = null;
        $pagamento->save();

        return redirect()->back()->with('success', 'Pagamento revertido.');
    }

    public function renew(Aluguel $aluguel): RedirectResponse
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$aluguel->imovel || 
            !$aluguel->imovel->propriedade || 
            $aluguel->imovel->propriedade->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }
        
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
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        $month = $request->input('month', Carbon::now()->startOfMonth()->toDateString());
        $ref = $this->normalizeMonthToStart($month);
        $refDate = Carbon::parse($ref)->startOfMonth()->toDateString();

        $aluguelId = $request->input('aluguel_id');

        $start = Carbon::parse($ref)->startOfMonth();
        $end = Carbon::parse($ref)->endOfMonth();

    $alugueisQuery = Aluguel::where(function ($q) use ($end) {
                $q->whereDate('data_inicio', '<=', $end->toDateString());
            })
            ->where(function ($q) use ($start) {
                $q->whereNull('data_fim')
                  ->orWhereDate('data_fim', '>=', $start->toDateString());
            })
            ->whereHas('imovel.propriedade', function ($q) use ($proprietarioId) {
                $q->where('proprietario_id', $proprietarioId);
            });

        if ($aluguelId) {
            $alugueisQuery->where('id', (int)$aluguelId);
        }

        $alugueis = $alugueisQuery->get();
        $now = now();
        
        foreach ($alugueis as $a) {
            $valorDevido = $a->valor_mensal ?? 0;

            $refMonthStart = Carbon::parse($refDate)->startOfMonth();
            $refMonthEnd = Carbon::parse($refDate)->endOfMonth();
            $totalDaysInMonth = $refMonthEnd->day;

            $effectiveStart = $refMonthStart->copy();
            $contratoInicio = Carbon::parse($a->data_inicio);
            if ($contratoInicio->between($refMonthStart, $refMonthEnd)) {
                $effectiveStart = $contratoInicio;
            }

            $effectiveEnd = $refMonthEnd->copy();
            if ($a->data_fim !== null) {
                $contratoFim = Carbon::parse($a->data_fim);
                if ($contratoFim->between($refMonthStart, $refMonthEnd)) {
                    $effectiveEnd = $contratoFim;
                }
            }

            $daysOccupied = $effectiveStart->diffInDays($effectiveEnd) + 1;

            if ($daysOccupied < $totalDaysInMonth) {
                $valorDevido = ($a->valor_mensal / $totalDaysInMonth) * $daysOccupied;
                $valorDevido = round($valorDevido, 2);
            }

            try {
                DB::table('pagamentos')->updateOrInsert(
                    [
                        'aluguel_id' => $a->id,
                        'referencia_mes' => $refDate,
                    ],
                    [
                        'valor_devido' => $valorDevido,
                        'valor_recebido' => $valorDevido,
                        'status' => 'paid',
                        'data_pago' => $now,
                        'updated_at' => $now,
                        'created_at' => DB::raw('COALESCE(created_at, NOW())'),
                    ]
                );
            } catch (\Exception $e) {
                Log::warning('Pagamento updateOrInsert failed (markAllPaid): ' . $e->getMessage());
            }
        }

        return redirect()->back()->with('success', 'Todos os pagamentos marcados como pagos.');
    }
}
