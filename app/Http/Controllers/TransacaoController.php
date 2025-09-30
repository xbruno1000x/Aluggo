<?php

namespace App\Http\Controllers;

use App\Models\Transacao;
use App\Models\Imovel;
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
        $transacoes = Transacao::with('imovel')->paginate(15);
        return view('transacoes.index', compact('transacoes'));
    }

    public function create(): View
    {
        $imoveis = Imovel::all();
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

        // Regra de negócio: não permite registrar venda se imóvel já estiver vendido
        if ($imovel && $imovel->status === 'vendido') {
            return redirect()->back()->withInput()->withErrors(['imovel_id' => 'Este imóvel já está marcado como vendido.']);
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $transacao = Transacao::create($data);

            // Marca o imovel como vendido
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
        // carregar relação imovel
        $transacao->load('imovel');

        $imovel = $transacao->imovel ?? null;
        $valorCompra = $imovel->valor_compra ?? null;
        $valorVenda = $transacao->valor_venda ?? null;

        $lucro = null;
        $porcentagem = null;
        $periodText = null;
        $selicText = null;
        $ipcaText = null;

        if ($valorCompra !== null && $valorVenda !== null) {
            $vc = (float) $valorCompra;
            $vv = (float) $valorVenda;
            $lucro = $vv - $vc;
            if ($vc != 0) {
                $porcentagem = ($lucro / $vc) * 100;
            }

            if (!empty($imovel->data_aquisicao)) {
                try {
                    $dtCompra = Carbon::parse($imovel->data_aquisicao);
                    // Use null coalescing and parse; avoids strict comparison/instanceof warnings in static analysis
                    $dtVenda = Carbon::parse($transacao->data_venda ?? now());
                    $diffDays = $dtCompra->diffInDays($dtVenda);
                    $periodYears = $diffDays > 0 ? ($diffDays / 365.0) : 0.0;
                    $periodText = $dtCompra->format('d/m/Y') . ' — ' . $dtVenda->format('d/m/Y') . " ({$diffDays} dias)";

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
                    }

                    if ($porcentagem !== null && isset($ipcaCumulative)) {
                        $gainDecimal = $porcentagem / 100.0;
                        $realGainDecimal = (1 + $gainDecimal) / (1 + $ipcaCumulative) - 1;
                        $realGainPercent = $realGainDecimal * 100;
                        $ipcaText .= ' | Seu lucro real, descontada a inflação no período, foi de ' . number_format($realGainPercent, 2, ',', '.') . '%';
                    }
                } catch (\Throwable $e) {
                    // log and ignore; view will simply not show comparative texts
                    Log::debug('FinanceRateService error: ' . $e->getMessage());
                }
            }
        }

        return view('transacoes.show', compact('transacao', 'lucro', 'porcentagem', 'periodText', 'selicText', 'ipcaText'));
    }

    public function edit(Transacao $transacao): View
    {
        $imoveis = Imovel::all();
        return view('transacoes.edit', compact('transacao', 'imoveis'));
    }

    public function update(Request $request, Transacao $transacao): RedirectResponse
    {
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
        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $transacaoId = $transacao->id;
            $imovelId = $transacao->imovel_id;

            $deleted = Transacao::destroy($transacaoId);

            if (! $deleted) {
                \Illuminate\Support\Facades\DB::table('transacoes')->where('id', $transacaoId)->delete();
            }

            // garantir que não exista mais (para lógica subsequente)
            $stillExists = \Illuminate\Support\Facades\DB::table('transacoes')->where('id', $transacaoId)->exists();
            if ($stillExists) {
                \Illuminate\Support\Facades\DB::rollBack();
                return redirect()->route('transacoes.index')->with('error', 'Falha ao excluir a transação. Tente novamente.');
            }

            // Se não existirem outras transações para este imóvel, reverte status para disponivel
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
}
