<?php

namespace App\Http\Controllers;

use App\Models\Transacao;
use App\Models\Imovel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

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
        return view('transacoes.show', compact('transacao'));
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
