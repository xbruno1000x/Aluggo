<?php

namespace App\Http\Controllers;

use App\Models\Propriedade;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;

class PropriedadeController extends Controller
{
    public function index(): View
    {
        $propriedades = Propriedade::where('proprietario_id', Auth::id())->get();

        return view('propriedades.index', ['propriedades' => $propriedades]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'endereco' => 'required|string|max:255',
            'descricao' => 'nullable|string',
        ]);

        $validated['proprietario_id'] = Auth::id();
        $propriedade = Propriedade::create($validated);

        return response()->json([
            'id' => $propriedade->id,
            'nome' => $propriedade->nome,
        ]);
    }

    public function edit(int $id): View
    {
        $propriedade = Propriedade::where('proprietario_id', Auth::id())->findOrFail($id);

        return view('propriedades.edit', ['propriedade' => $propriedade]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $validated = $request->validate([
            'nome' => 'required|string|max:255',
            'endereco' => 'required|string|max:255',
            'descricao' => 'nullable|string',
        ]);

        $propriedade = Propriedade::where('proprietario_id', Auth::id())->findOrFail($id);
        $propriedade->update($validated);

        return redirect()->route('propriedades.index')->with('success', 'Propriedade atualizada com sucesso!');
    }

    public function destroy(int $id): RedirectResponse
    {
        $propriedade = Propriedade::where('proprietario_id', Auth::id())->findOrFail($id);
        $propriedade->delete();

        return redirect()->route('propriedades.index')->with('success', 'Propriedade excluída com sucesso!');
    }
}
