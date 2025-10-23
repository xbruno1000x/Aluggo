<?php

namespace App\Http\Controllers;

use App\Models\Transacao;
use App\Models\Imovel;
use App\Models\Aluguel;
use App\Models\Pagamento;
use App\Models\Obra;
use App\Models\Taxa;
use App\Services\FinanceRateService;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class TransacaoController extends Controller
{
    public function index(): View
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        $transacoes = Transacao::with('imovel')
            ->whereHas('imovel.propriedade', function ($q) use ($proprietarioId) {
                $q->where('proprietario_id', $proprietarioId);
            })
            ->paginate(15);
            
        return view('transacoes.index', compact('transacoes'));
    }

    public function create(): View
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        $imoveis = Imovel::whereHas('propriedade', function ($q) use ($proprietarioId) {
                $q->where('proprietario_id', $proprietarioId);
            })
            ->get();
            
        return view('transacoes.create', compact('imoveis'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'valor_venda' => ['required', 'numeric', 'min:0.01'],
            'data_venda' => ['required', 'date'],
            'imovel_id' => ['required', 'exists:imoveis,id'],
        ]);

        $imovel = Imovel::find($data['imovel_id']);

        if ($imovel && $imovel->status === 'vendido') {
            return redirect()->back()->withInput()->withErrors(['imovel_id' => 'Este imóvel já está marcado como vendido.']);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $transacao = Transacao::create($data);

            if ($imovel) {
                $imovel->status = 'vendido';
                $imovel->save();
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('transacoes.index');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['general' => 'Erro ao salvar transação. Tente novamente.']);
        }
    }

    public function show(Transacao $transacao): View
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$transacao->imovel || 
            !$transacao->imovel->propriedade || 
            $transacao->imovel->propriedade->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }
        
        $transacao->load('imovel');

        $imovel = $transacao->imovel ?? null;
        $valorCompra = $imovel->valor_compra ?? null;
        $valorVenda = $transacao->valor_venda ?? null;

        $lucro = 0.0;
        $porcentagem = null;
        $periodText = null;
        $selicText = null;
        $ipcaText = null;
        $selicCumulative = null;
        $ipcaCumulative = null;
        $rentalIncome = 0.0;
        $obraExpenses = 0.0;
        $taxExpenses = 0.0;
        $adjustedProfit = 0.0;

        if ($valorCompra !== null && $valorVenda !== null) {
            $vc = (float) $valorCompra;
            $vv = (float) $valorVenda;
            $lucro = $vv - $vc;
            if ($vc != 0.0) {
                $porcentagem = ($lucro / $vc) * 100;
            }

            if ($imovel && !empty($imovel->data_aquisicao)) {
                try {
                    $dtCompra = Carbon::parse($imovel->data_aquisicao);
                    $dtVenda = Carbon::parse($transacao->data_venda ?? now());
                    $diffDays = $dtCompra->diffInDays($dtVenda);
                    $periodYears = $diffDays > 0 ? ($diffDays / 365.0) : 0.0;
                    $periodText = $dtCompra->format('d/m/Y') . ' — ' . $dtVenda->format('d/m/Y') . " ({$diffDays} dias)";

                    $start = Carbon::parse($imovel->data_aquisicao)->startOfMonth();
                    $end = Carbon::parse($transacao->data_venda ?? now())->startOfMonth();

                    $alugueis = Aluguel::where('imovel_id', $imovel->id)
                        ->whereDate('data_inicio', '<=', $end->toDateString())
                        ->where(function ($q) use ($start) {
                            $q->whereNull('data_fim')
                                ->orWhereDate('data_fim', '>=', $start->toDateString());
                        })
                        ->get();

                    $rentalIncome = 0.0;
                    foreach ($alugueis as $aluguel) {
                        $sum = Pagamento::where('aluguel_id', $aluguel->id)
                            ->whereDate('referencia_mes', '>=', $start->toDateString())
                            ->whereDate('referencia_mes', '<=', $end->toDateString())
                            ->sum('valor_recebido');
                        $rentalIncome += (float) $sum;
                    }

                    $obraExpenses = (float) Obra::where('imovel_id', $imovel->id)
                        ->whereDate('data_inicio', '<=', $end->toDateString())
                        ->where(function ($q) use ($start) {
                            $q->whereNull('data_fim')
                                ->orWhereDate('data_fim', '>=', $start->toDateString());
                        })
                        ->sum('valor');

                    $taxExpenses = 0.0;
                    $taxQueryEnd = $end->copy()->endOfMonth();

                    $taxas = Taxa::with(['propriedade.imoveis:id,propriedade_id', 'aluguel.imovel:id'])
                        ->whereDate('data_pagamento', '>=', $start->toDateString())
                        ->whereDate('data_pagamento', '<=', $taxQueryEnd->toDateString())
                        ->where(function ($query) use ($imovel) {
                            $query->where('imovel_id', $imovel->id)
                                ->orWhereHas('aluguel', function ($q2) use ($imovel) {
                                    $q2->where('imovel_id', $imovel->id);
                                })
                                ->orWhereHas('propriedade', function ($q3) use ($imovel) {
                                    $q3->whereHas('imoveis', function ($q4) use ($imovel) {
                                        $q4->where('id', $imovel->id);
                                    });
                                });
                        })
                        ->get();

                    foreach ($taxas as $taxa) {
                        if (($taxa->pagador ?? '') !== 'proprietario') {
                            continue;
                        }

                        if (!empty($taxa->imovel_id) && (int) $taxa->imovel_id === (int) $imovel->id) {
                            $taxExpenses += (float) $taxa->valor;
                            continue;
                        }

                        if ($taxa->aluguel && $taxa->aluguel->imovel && (int) $taxa->aluguel->imovel->id === (int) $imovel->id) {
                            $taxExpenses += (float) $taxa->valor;
                            continue;
                        }

                        if ($taxa->propriedade) {
                            $ids = $taxa->propriedade->imoveis->pluck('id')->map(fn ($id) => (int) $id)->all();
                            if (!empty($ids) && in_array((int) $imovel->id, $ids, true)) {
                                $share = (float) $taxa->valor / count($ids);
                                $taxExpenses += $share;
                            }
                        }
                    }

                    $adjustedProfit = $lucro + $rentalIncome - $obraExpenses - $taxExpenses;

                    $svc = app(FinanceRateService::class);
                    $startYmd = $dtCompra->format('Y-m-d');
                    $endYmd = $dtVenda->format('Y-m-d');

                    /** @var array{value:float,type:'cumulative'|'annual'}|null $selicResult */
                    $selicResult = $svc->getCumulativeReturn($startYmd, $endYmd, 'selic');
                    if ($selicResult !== null) {
                        $selicType = $selicResult['type'];
                        $selicValue = (float) $selicResult['value'];
                        if ($selicType === 'cumulative') {
                            $selicCumulative = $selicValue;
                        } else {
                            $selicCumulative = pow(1 + $selicValue, $periodYears) - 1;
                        }
                        $selicText = 'Se você tivesse investido no SELIC/CDI no mesmo período, teria rendido ' . number_format($selicCumulative * 100, 2, ',', '.') . '%';
                    }

                    /** @var array{value:float,type:'cumulative'|'annual'}|null $ipcaResult */
                    $ipcaResult = $svc->getCumulativeReturn($startYmd, $endYmd, 'ipca');
                    if ($ipcaResult !== null) {
                        $ipcaType = $ipcaResult['type'];
                        $ipcaValue = (float) $ipcaResult['value'];
                        if ($ipcaType === 'cumulative') {
                            $ipcaCumulative = $ipcaValue;
                        } else {
                            $ipcaCumulative = pow(1 + $ipcaValue, $periodYears) - 1;
                        }
                        $ipcaText = 'Inflação (IPCA) no período: ' . number_format($ipcaCumulative * 100, 2, ',', '.') . '%';

                        if ($ipcaCumulative < -0.5) {
                            $ipcaCumulative = abs($ipcaCumulative);
                        }
                    }

                    if ($vc != 0.0 && $ipcaCumulative !== null) {
                        $gainDecimal = $adjustedProfit / (float) $vc;
                        $realGainDecimal = (1 + $gainDecimal) / (1 + $ipcaCumulative) - 1;
                        $realGainPercent = $realGainDecimal * 100;

                        $ipcaText .= ' | Seu lucro real, descontada a inflação no período, foi de: ' . number_format($realGainPercent, 2, ',', '.') . '%';
                    }
                } catch (\Throwable $e) {
                    Log::debug('FinanceRateService error: ' . $e->getMessage());
                }
            }
        }

        return view('transacoes.show', compact('transacao', 'lucro', 'porcentagem', 'periodText', 'selicText', 'ipcaText', 'rentalIncome', 'obraExpenses', 'adjustedProfit', 'taxExpenses'));
    }

    public function edit(Transacao $transacao): View
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$transacao->imovel || 
            !$transacao->imovel->propriedade || 
            $transacao->imovel->propriedade->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }
        
        $imoveis = Imovel::whereHas('propriedade', function ($q) use ($proprietarioId) {
                $q->where('proprietario_id', $proprietarioId);
            })
            ->get();
            
        return view('transacoes.edit', compact('transacao', 'imoveis'));
    }

    public function update(Request $request, Transacao $transacao): RedirectResponse
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$transacao->imovel || 
            !$transacao->imovel->propriedade || 
            $transacao->imovel->propriedade->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }
        
        $data = $request->validate([
            'valor_venda' => ['required', 'numeric', 'min:0.01'],
            'data_venda' => ['required', 'date'],
            'imovel_id' => ['required', 'exists:imoveis,id'],
        ]);

        $transacao->update($data);
        return redirect()->route('transacoes.index');
    }

    public function destroy(Transacao $transacao): RedirectResponse
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$transacao->imovel || 
            !$transacao->imovel->propriedade || 
            $transacao->imovel->propriedade->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }
        
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $transacaoId = $transacao->id;
            $imovelId = $transacao->imovel_id;

            $deleted = Transacao::destroy($transacaoId);

            if (! $deleted) {
                \Illuminate\Support\Facades\DB::table('transacoes')->where('id', $transacaoId)->delete();
            }

            $stillExists = \Illuminate\Support\Facades\DB::table('transacoes')->where('id', $transacaoId)->exists();
            if ($stillExists) {
                \Illuminate\Support\Facades\DB::rollBack();
                return redirect()->route('transacoes.index')->with('error', 'Falha ao excluir a transação. Tente novamente.');
            }

            $hasOther = Transacao::where('imovel_id', $imovelId)->exists();
            if (! $hasOther) {
                $im = Imovel::find($imovelId);
                if ($im) {
                    $im->status = 'disponivel';
                    $im->save();
                }
            }

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('transacoes.index')->with('success', 'Transação excluída com sucesso.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->route('transacoes.index')->with('error', 'Falha ao excluir a transação. Tente novamente.');
        }
    }

    /**
     * Exibe formulário de simulação de venda
     */
    public function showSimulation(Imovel $imovel): View
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$imovel->propriedade || $imovel->propriedade->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }

        $valorSugerido = $imovel->valor_compra ?? 0;
        
        $ultimoAluguel = Aluguel::where('imovel_id', $imovel->id)
            ->orderBy('data_inicio', 'desc')
            ->first();
        
        if ($ultimoAluguel && $ultimoAluguel->valor_mensal > 2) {
            $valorBaseadoEmAluguel = ($ultimoAluguel->valor_mensal * 12) / 0.13;
            $valorSugerido = max($valorSugerido, $valorBaseadoEmAluguel);
        } else {
            if ($valorSugerido > 0 && !empty($imovel->data_aquisicao)) {
                $dataAquisicao = Carbon::parse($imovel->data_aquisicao);
                $anosDePosse = $dataAquisicao->diffInDays(now()) / 365.0;
                
                $valorSugerido = $valorSugerido * pow(1.13, $anosDePosse);
            }
        }

        return view('transacoes.simulate', compact('imovel', 'valorSugerido'));
    }

    /**
     * Processa simulação de venda (sem salvar)
     */
    public function simulate(Request $request, Imovel $imovel): View
    {
        // Aumenta o timeout para 60 segundos devido às consultas ao BACEN
        set_time_limit(60);
        
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$imovel->propriedade || $imovel->propriedade->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }

        $data = $request->validate([
            'valor_venda' => ['required', 'numeric', 'min:0.01'],
            'data_venda' => ['required', 'date'],
        ]);

        $valorVenda = (float) $data['valor_venda'];
        $dataVenda = $data['data_venda'];

        $valorCompra = $imovel->valor_compra ?? null;
        
        $lucro = 0.0;
        $porcentagem = null;
        $periodText = null;
        $selicText = null;
        $ipcaText = null;
        $selicCumulative = null;
        $ipcaCumulative = null;
        $rentalIncome = 0.0;
        $obraExpenses = 0.0;
        $taxExpenses = 0.0;
        $adjustedProfit = 0.0;

        if ($valorCompra !== null) {
            $vc = (float) $valorCompra;
            $lucro = $valorVenda - $vc;
            if ($vc != 0.0) {
                $porcentagem = ($lucro / $vc) * 100;
            }

            if (!empty($imovel->data_aquisicao)) {
                try {
                    $dtCompra = Carbon::parse($imovel->data_aquisicao);
                    $dtVenda = Carbon::parse($dataVenda);
                    $diffDays = $dtCompra->diffInDays($dtVenda);
                    $periodYears = $diffDays > 0 ? ($diffDays / 365.0) : 0.0;
                    $periodText = $dtCompra->format('d/m/Y') . ' — ' . $dtVenda->format('d/m/Y') . " ({$diffDays} dias)";

                    $start = Carbon::parse($imovel->data_aquisicao)->startOfMonth();
                    $end = Carbon::parse($dataVenda)->startOfMonth();

                    $alugueis = Aluguel::where('imovel_id', $imovel->id)
                        ->whereDate('data_inicio', '<=', $end->toDateString())
                        ->where(function ($q) use ($start) {
                            $q->whereNull('data_fim')
                                ->orWhereDate('data_fim', '>=', $start->toDateString());
                        })
                        ->get();

                    foreach ($alugueis as $aluguel) {
                        $sum = Pagamento::where('aluguel_id', $aluguel->id)
                            ->whereDate('referencia_mes', '>=', $start->toDateString())
                            ->whereDate('referencia_mes', '<=', $end->toDateString())
                            ->sum('valor_recebido');
                        $rentalIncome += (float) $sum;
                    }

                    $obraExpenses = (float) Obra::where('imovel_id', $imovel->id)
                        ->whereDate('data_inicio', '<=', $end->toDateString())
                        ->where(function ($q) use ($start) {
                            $q->whereNull('data_fim')
                                ->orWhereDate('data_fim', '>=', $start->toDateString());
                        })
                        ->sum('valor');

                    $taxQueryEnd = $end->copy()->endOfMonth();
                    $taxas = Taxa::with(['propriedade.imoveis:id,propriedade_id', 'aluguel.imovel:id'])
                        ->whereDate('data_pagamento', '>=', $start->toDateString())
                        ->whereDate('data_pagamento', '<=', $taxQueryEnd->toDateString())
                        ->where(function ($query) use ($imovel) {
                            $query->where('imovel_id', $imovel->id)
                                ->orWhereHas('aluguel', function ($q2) use ($imovel) {
                                    $q2->where('imovel_id', $imovel->id);
                                })
                                ->orWhereHas('propriedade', function ($q3) use ($imovel) {
                                    $q3->whereHas('imoveis', function ($q4) use ($imovel) {
                                        $q4->where('id', $imovel->id);
                                    });
                                });
                        })
                        ->get();

                    foreach ($taxas as $taxa) {
                        if (($taxa->pagador ?? '') !== 'proprietario') {
                            continue;
                        }

                        if (!empty($taxa->imovel_id) && (int) $taxa->imovel_id === (int) $imovel->id) {
                            $taxExpenses += (float) $taxa->valor;
                            continue;
                        }

                        if ($taxa->aluguel && $taxa->aluguel->imovel && (int) $taxa->aluguel->imovel->id === (int) $imovel->id) {
                            $taxExpenses += (float) $taxa->valor;
                            continue;
                        }

                        if ($taxa->propriedade) {
                            $ids = $taxa->propriedade->imoveis->pluck('id')->map(fn ($id) => (int) $id)->all();
                            if (!empty($ids) && in_array((int) $imovel->id, $ids, true)) {
                                $share = (float) $taxa->valor / count($ids);
                                $taxExpenses += $share;
                            }
                        }
                    }

                    $adjustedProfit = $lucro + $rentalIncome - $obraExpenses - $taxExpenses;

                    // Calcular indicadores financeiros (SELIC e IPCA) com timeout protection
                    try {
                        $svc = app(FinanceRateService::class);
                        $startYmd = $dtCompra->format('Y-m-d');
                        $endYmd = $dtVenda->format('Y-m-d');

                        // Tentar buscar SELIC com timeout
                        try {
                            $selicResult = $svc->getCumulativeReturn($startYmd, $endYmd, 'selic');
                            if ($selicResult !== null) {
                                $selicType = $selicResult['type'];
                                $selicValue = (float) $selicResult['value'];
                                if ($selicType === 'cumulative') {
                                    $selicCumulative = $selicValue;
                                } else {
                                    $selicCumulative = pow(1 + $selicValue, $periodYears) - 1;
                                }
                                $selicText = 'Se você tivesse investido no SELIC/CDI no mesmo período, teria rendido ' . number_format($selicCumulative * 100, 2, ',', '.') . '%';
                            }
                        } catch (\Throwable $selicError) {
                            Log::warning('SELIC calculation timeout/error: ' . $selicError->getMessage());
                            $selicText = 'Dados de SELIC temporariamente indisponíveis';
                        }

                        // Tentar buscar IPCA com timeout
                        try {
                            $ipcaResult = $svc->getCumulativeReturn($startYmd, $endYmd, 'ipca');
                            if ($ipcaResult !== null) {
                                $ipcaType = $ipcaResult['type'];
                                $ipcaValue = (float) $ipcaResult['value'];
                                if ($ipcaType === 'cumulative') {
                                    $ipcaCumulative = $ipcaValue;
                                } else {
                                    $ipcaCumulative = pow(1 + $ipcaValue, $periodYears) - 1;
                                }
                                $ipcaText = 'Inflação (IPCA) no período: ' . number_format($ipcaCumulative * 100, 2, ',', '.') . '%';

                                if ($ipcaCumulative < -0.5) {
                                    $ipcaCumulative = abs($ipcaCumulative);
                                }
                            }
                        } catch (\Throwable $ipcaError) {
                            Log::warning('IPCA calculation timeout/error: ' . $ipcaError->getMessage());
                            $ipcaText = 'Dados de IPCA temporariamente indisponíveis';
                        }

                        if ($vc != 0.0 && $ipcaCumulative !== null) {
                            $gainDecimal = $adjustedProfit / (float) $vc;
                            $realGainDecimal = (1 + $gainDecimal) / (1 + $ipcaCumulative) - 1;
                            $realGainPercent = $realGainDecimal * 100;

                            $ipcaText .= ' | Seu lucro real, descontada a inflação no período, foi de: ' . number_format($realGainPercent, 2, ',', '.') . '%';
                        }
                    } catch (\Throwable $financeError) {
                        Log::warning('Finance service error in simulation: ' . $financeError->getMessage());
                        // Continua sem os dados financeiros, mas não quebra a simulação
                    }
                } catch (\Throwable $e) {
                    Log::debug('Simulation calculation error: ' . $e->getMessage());
                }
            }
        }

        $simulacao = [
            'valor_venda' => $valorVenda,
            'data_venda' => $dataVenda,
        ];

        return view('transacoes.simulate-result', compact(
            'imovel', 
            'simulacao',
            'lucro', 
            'porcentagem', 
            'periodText', 
            'selicText', 
            'ipcaText', 
            'rentalIncome', 
            'obraExpenses', 
            'adjustedProfit', 
            'taxExpenses'
        ));
    }
}
