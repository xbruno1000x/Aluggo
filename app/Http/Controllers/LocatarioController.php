<?php

namespace App\Http\Controllers;

use App\Models\Locatario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class LocatarioController extends Controller
{
    public function index(Request $request): View
    {
        $proprietarioId = Auth::id();
        
        $query = Locatario::where('proprietario_id', $proprietarioId);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('telefone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $locatarios = $query->orderBy('nome')->get();
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

        $validated['proprietario_id'] = Auth::id();

        Locatario::create($validated);

        return redirect()->route('locatarios.index')
            ->with('success', 'Locatário cadastrado com sucesso!');
    }

    public function edit(Locatario $locatario): View
    {
        $proprietarioId = Auth::id();
        
        if ($locatario->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }
        
        return view('locatarios.edit', compact('locatario'));
    }

    public function update(Request $request, Locatario $locatario): RedirectResponse
    {
        $proprietarioId = Auth::id();
        
        if ($locatario->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }
        
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
        $proprietarioId = Auth::id();
        
        if ($locatario->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }
        
        $locatario->delete();

        return redirect()->route('locatarios.index')
            ->with('success', 'Locatário excluído com sucesso!');
    }
}
