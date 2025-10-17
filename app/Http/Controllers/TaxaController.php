<?php

namespace App\Http\Controllers;

use App\Models\Taxa;
use App\Models\Imovel;
use App\Models\Aluguel;
use App\Models\Propriedade;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class TaxaController extends Controller
{
    public function index(Request $request): View
    {
        $query = Taxa::with(['imovel', 'propriedade'])
            ->where(function ($q) {
                $q->whereHas('imovel.propriedade', function ($q2) {
                    $q2->where('proprietario_id', Auth::id());
                })
                    ->orWhere('proprietario_id', Auth::id());
            });

        if ($request->filled('imovel_id')) {
            $query->where('imovel_id', (int) $request->imovel_id);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('pagador')) {
            $query->where('pagador', $request->pagador);
        }

        if ($request->filled('start')) {
            try {
                $start = Carbon::parse($request->start)->startOfDay()->toDateString();
                $query->whereDate('data_pagamento', '>=', $start);
            } catch (\Exception $e) {

            }
        }

        if ($request->filled('end')) {
            try {
                $end = Carbon::parse($request->end)->endOfDay()->toDateString();
                $query->whereDate('data_pagamento', '<=', $end);
            } catch (\Exception $e) {

            }
        }

        $taxas = $query->orderByDesc('data_pagamento')->paginate(20)->withQueryString();

        $imoveis = Imovel::whereHas('propriedade', function ($q) {
            $q->where('proprietario_id', Auth::id());
        })->get();
        $propriedades = Propriedade::where('proprietario_id', Auth::id())->orderBy('nome')->get();
        return view('taxas.index', compact('taxas', 'imoveis', 'propriedades'));
    }

    public function create(): View
    {
        $imoveis = Imovel::whereHas('propriedade', function ($q) {
            $q->where('proprietario_id', Auth::id());
        })->get();
        $propriedades = Propriedade::where('proprietario_id', Auth::id())->orderBy('nome')->get();
        return view('taxas.create', compact('imoveis', 'propriedades'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'imovel_id' => 'nullable|exists:imoveis,id',
            'propriedade_id' => 'nullable|exists:propriedades,id',
            'aluguel_id' => 'nullable|exists:alugueis,id',
            'tipo' => 'required|string|max:50',
            'valor' => 'required|numeric|min:0',
            'data_pagamento' => 'required|date',
            'pagador' => 'required|in:proprietario,locatario',
            'observacao' => 'nullable|string',
        ]);

        if (!empty($validated['imovel_id']) && !empty($validated['propriedade_id'])) {
            return redirect()->back()->withInput()->with('error', 'Escolha apenas um: imóvel ou propriedade.');
        }

        if (!empty($validated['propriedade_id'])) {
            $prop = Propriedade::find($validated['propriedade_id']);
            if (!$prop || $prop->proprietario_id !== Auth::id()) {
                abort(403);
            }
        }

        $validated['proprietario_id'] = Auth::id();
        Taxa::create($validated);
        return redirect()->route('taxas.index')->with('success', 'Taxa registrada.');
    }

    public function edit(Taxa $taxa): View
    {
        $this->ensureOwnerOrAbort($taxa);
        $imoveis = Imovel::whereHas('propriedade', function ($q) {
            $q->where('proprietario_id', Auth::id());
        })->get();
        $propriedades = Propriedade::where('proprietario_id', Auth::id())->orderBy('nome')->get();
        return view('taxas.edit', compact('taxa', 'imoveis', 'propriedades'));
    }

    public function update(Request $request, Taxa $taxa): RedirectResponse
    {
        $this->ensureOwnerOrAbort($taxa);
        $validated = $request->validate([
            'imovel_id' => 'nullable|exists:imoveis,id',
            'propriedade_id' => 'nullable|exists:propriedades,id',
            'aluguel_id' => 'nullable|exists:alugueis,id',
            'tipo' => 'required|string|max:50',
            'valor' => 'required|numeric|min:0',
            'data_pagamento' => 'required|date',
            'pagador' => 'required|in:proprietario,locatario',
            'observacao' => 'nullable|string',
        ]);
        if (!empty($validated['imovel_id']) && !empty($validated['propriedade_id'])) {
            return redirect()->back()->withInput()->with('error', 'Escolha apenas um: imóvel ou propriedade.');
        }

        if (!empty($validated['propriedade_id'])) {
            $prop = Propriedade::find($validated['propriedade_id']);
            if (!$prop || $prop->proprietario_id !== Auth::id()) {
                abort(403);
            }
        }
        $this->validateOwnershipForInput($validated);

        $taxa->update($validated);
        return redirect()->route('taxas.index')->with('success', 'Taxa atualizada.');
    }

    public function destroy(Taxa $taxa): RedirectResponse
    {
        $this->ensureOwnerOrAbort($taxa);
        $taxa->delete();
        return redirect()->route('taxas.index')->with('success', 'Taxa excluída.');
    }

    /**
     * Verifica se o usuário atual é o proprietário do imóvel/aluguel associado à taxa.
     * Aborta com 403 caso contrário.
     *
     * @param \App\Models\Taxa $taxa
     */
    private function ensureOwnerOrAbort(Taxa $taxa): void
    {
        // help phpstan understand loaded relations
        /** @var \App\Models\Taxa $taxa */
        $taxa->loadMissing(['imovel.propriedade', 'aluguel.imovel.propriedade', 'propriedade']);

        $userId = Auth::id();

        if ($taxa->imovel && $taxa->imovel->propriedade && $taxa->imovel->propriedade->proprietario_id === $userId) {
            return;
        }

        if ($taxa->aluguel && $taxa->aluguel->imovel && $taxa->aluguel->imovel->propriedade && $taxa->aluguel->imovel->propriedade->proprietario_id === $userId) {
            return;
        }

        if ($taxa->propriedade && $taxa->propriedade->proprietario_id === $userId) {
            return;
        }

        if (!empty($taxa->proprietario_id) && $taxa->proprietario_id === $userId) {
            return;
        }

        abort(403);
    }

    /**
     * Valida que imovel_id e aluguel_id (quando presentes) pertencem ao usuário autenticado.
     * Lança 403 se qualquer um pertencer a outro usuário.
     *
     * @param array<string,mixed> $input
     */
    private function validateOwnershipForInput(array $input): void
    {
        $userId = Auth::id();

        if (!empty($input['imovel_id'])) {
            $imovel = Imovel::with('propriedade')->find($input['imovel_id']);
            if (!$imovel || !$imovel->propriedade || $imovel->propriedade->proprietario_id !== $userId) {
                abort(403);
            }
        }

        if (!empty($input['aluguel_id'])) {
            $aluguel = Aluguel::with('imovel.propriedade')->find($input['aluguel_id']);
            if (!$aluguel || !$aluguel->imovel || !$aluguel->imovel->propriedade || $aluguel->imovel->propriedade->proprietario_id !== $userId) {
                abort(403);
            }
        }
    }
}
