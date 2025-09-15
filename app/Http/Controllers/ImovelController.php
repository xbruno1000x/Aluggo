<?php

namespace App\Http\Controllers;

use App\Models\Imovel;
use App\Models\Propriedade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ImovelController extends Controller
{
    public function index(): View
    {
        // Obter todos os imóveis do usuário logado
        $imoveis = Imovel::whereHas('propriedade', function ($query) {
            $query->where('proprietario_id', Auth::id());
        })->orderBy('tipo')->get();

        return view('imoveis.index', compact('imoveis'));
    }

    public function create(): View
    {
        // Buscar todas as propriedades do usuário logado
        $propriedades = Propriedade::where('proprietario_id', Auth::id())->get();
        return view('imoveis.create', compact('propriedades'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:apartamento,terreno,loja',
            'valor_compra' => 'nullable|numeric',
            'status' => 'required|in:disponível,vendido,alugado',
            'data_aquisicao' => 'nullable|date',
            'propriedade_id' => 'required|exists:propriedades,id',
        ]);

        // Verificar se a propriedade pertence ao usuário logado
        $propriedade = Propriedade::where('id', $validated['propriedade_id'])
                                  ->where('proprietario_id', Auth::id())
                                  ->firstOrFail();

        Imovel::create($validated);
        return redirect()->route('imoveis.index')->with('success', 'Imóvel cadastrado com sucesso!');
    }

    public function edit(Imovel $imovel): View
    {
        // Verificar se o imóvel pertence ao usuário logado
        if ($imovel->propriedade->proprietario_id !== Auth::id()) {
            abort(403, 'Acesso não autorizado');
        }

        // Buscar todas as propriedades do usuário logado
        $propriedades = Propriedade::where('proprietario_id', Auth::id())->get();
        return view('imoveis.edit', compact('imovel', 'propriedades'));
    }

    public function update(Request $request, Imovel $imovel): RedirectResponse
    {
        // Verificar se o imóvel pertence ao usuário logado
        if ($imovel->propriedade->proprietario_id !== Auth::id()) {
            abort(403, 'Acesso não autorizado');
        }

        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'tipo' => 'required|in:apartamento,terreno,loja',
            'valor_compra' => 'nullable|numeric',
            'status' => 'required|in:disponível,vendido,alugado',
            'data_aquisicao' => 'nullable|date',
            'propriedade_id' => 'required|exists:propriedades,id',
        ]);

        // Verificar se a propriedade pertence ao usuário logado
        $propriedade = Propriedade::where('id', $validated['propriedade_id'])
                                  ->where('proprietario_id', Auth::id())
                                  ->firstOrFail();

        $imovel->update($validated);

        return redirect()->route('imoveis.index')->with('success', 'Imóvel atualizado com sucesso!');
    }

    public function destroy(Imovel $imovel): RedirectResponse
    {
        // Verificar se o imóvel pertence ao usuário logado
        if ($imovel->propriedade->proprietario_id !== Auth::id()) {
            abort(403, 'Acesso não autorizado');
        }

        $imovel->delete();
        return redirect()->route('imoveis.index')->with('success', 'Imóvel excluído com sucesso!');
    }
}