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
        $query = Locatario::query();

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nome', 'like', "%{$search}%")
                    ->orWhere('telefone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $locatarios = $query->orderBy('nome')->get();

        $locatarios->transform(function ($item) {
            $raw = preg_replace('/[^0-9]/', '', (string) $item->telefone);
            $formatted = $item->telefone;
            if (strlen($raw) === 11) {
                // celular: (AA)NNNNN-NNNN
                $formatted = sprintf('(%s)%s-%s', substr($raw, 0, 2), substr($raw, 2, 5), substr($raw, 7));
            } elseif (strlen($raw) === 10) {
                // fixo: (AA)NNNN-NNNN
                $formatted = sprintf('(%s)%s-%s', substr($raw, 0, 2), substr($raw, 2, 4), substr($raw, 6));
            } elseif ($raw === '') {
                $formatted = '';
            }
            $item->telefone = $formatted;
            return $item;
        });

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
        $locatario->delete();

        return redirect()->route('locatarios.index')
            ->with('success', 'Locatário excluído com sucesso!');
    }
}
