<?php

namespace App\Http\Controllers;

use App\Models\Locatario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LocatarioController extends Controller
{
    public function index(): View
    {
        // Lista apenas os locatários vinculados às propriedades do usuário logado (via alugueis)
        $locatarios = Locatario::whereHas('alugueis.imovel.propriedade', function ($query) {
            $query->where('proprietario_id', Auth::id());
        })->orderBy('nome')->get();

        return view('locatarios.index', compact('locatarios'));
    }

    public function create(): View
    {
        return view('locatarios.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nome'     => 'required|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
        ]);

        Locatario::create($validated);

        return redirect()->route('locatarios.index')
            ->with('success', 'Locatário cadastrado com sucesso!');
    }

    public function edit(Locatario $locatario): View
    {
        // Aqui não tem vínculo direto com usuário, mas poderíamos validar pelo relacionamento com aluguel
        return view('locatarios.edit', compact('locatario'));
    }

    public function update(Request $request, Locatario $locatario): RedirectResponse
    {
        $validated = $request->validate([
            'nome'     => 'required|string|max:255',
            'telefone' => 'nullable|string|max:20',
            'email'    => 'nullable|email|max:255',
        ]);

        $locatario->update($validated);

        return redirect()->route('locatarios.index')
            ->with('success', 'Locatário atualizado com sucesso!');
    }

    public function destroy(Locatario $locatario): RedirectResponse
    {
        // Antes de excluir, poderia validar se pertence a algum aluguel do usuário logado
        $locatario->delete();

        return redirect()->route('locatarios.index')
            ->with('success', 'Locatário excluído com sucesso!');
    }
}