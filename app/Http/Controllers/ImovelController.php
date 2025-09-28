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
    public function index(Request $request): View
    {
        $query = Imovel::whereHas('propriedade', function ($q) {
            $q->where('proprietario_id', Auth::id());
        });

        // Filtros
        if ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . $request->nome . '%');
        }

        if ($request->filled('numero')) {
            $query->where('numero', 'like', '%' . $request->numero . '%');
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('propriedade_id')) {
            $query->where('propriedade_id', $request->propriedade_id);
        }

        $imoveis = $query->orderBy('tipo')->get();

        // Para popular o filtro de propriedades
        $propriedades = Propriedade::where('proprietario_id', Auth::id())->get();

        return view('imoveis.index', compact('imoveis', 'propriedades'));
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
            'numero' => 'nullable|string|max:50',
            'tipo' => 'required|in:apartamento,terreno,loja,casa,garagem',
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
            'numero' => 'nullable|string|max:50',
            'tipo' => 'required|in:apartamento,terreno,loja,casa,garagem',
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
