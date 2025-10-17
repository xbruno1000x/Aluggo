<?php

namespace App\Http\Controllers;

use App\Models\Obra;
use App\Models\Imovel;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class ObraController extends Controller
{
    /**
    * Exibe uma listagem do recurso.
     */
    public function index(Request $request): View
    {
        $query = Obra::with('imovel.propriedade');

        if ($request->filled('q')) {
            $query->where('descricao', 'like', '%'.$request->get('q').'%');
        }

        if ($request->filled('imovel_id')) {
            $query->where('imovel_id', $request->get('imovel_id'));
        }

        // filter by data_inicio range
        if ($request->filled('data_inicio_from')) {
            $query->whereDate('data_inicio', '>=', $request->get('data_inicio_from'));
        }

        if ($request->filled('data_inicio_to')) {
            $query->whereDate('data_inicio', '<=', $request->get('data_inicio_to'));
        }

        $obras = $query->orderByDesc('data_inicio')->paginate(15)->withQueryString();

        $imoveis = Imovel::orderBy('nome')->get();

        return view('obras.index', compact('obras', 'imoveis'));
    }

    /**
    * Exibe o formulário para criar um novo recurso.
     */
    public function create(): View
    {
        $imoveis = Imovel::orderBy('nome')->get();
        return view('obras.create', compact('imoveis'));
    }

    /**
    * Armazena um novo recurso.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'descricao' => ['required', 'string', 'max:1000'],
            'valor' => ['required', 'numeric', 'min:0'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'imovel_id' => ['required', 'exists:imoveis,id'],
        ]);

        DB::beginTransaction();
        try {
            Obra::create($data);
            DB::commit();
            return redirect()->route('obras.index')->with('success', 'Obra cadastrada com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['general' => 'Erro ao salvar obra.']);
        }
    }

    /**
    * Exibe o formulário para editar o recurso especificado.
     */
    public function edit(Obra $obra): View
    {
        $imoveis = Imovel::orderBy('nome')->get();
        return view('obras.edit', compact('obra', 'imoveis'));
    }

    /**
    * Atualiza o recurso especificado no armazenamento.
     */
    public function update(Request $request, Obra $obra): RedirectResponse
    {
        $data = $request->validate([
            'descricao' => ['required', 'string', 'max:1000'],
            'valor' => ['required', 'numeric', 'min:0'],
            'data_inicio' => ['nullable', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'imovel_id' => ['required', 'exists:imoveis,id'],
        ]);

        DB::beginTransaction();
        try {
            $obra->update($data);
            DB::commit();
            return redirect()->route('obras.index')->with('success', 'Obra atualizada com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['general' => 'Erro ao atualizar obra.']);
        }
    }

    /**
    * Remove o recurso especificado do armazenamento.
     */
    public function destroy(Obra $obra): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $obra->delete();
            DB::commit();
            return redirect()->route('obras.index')->with('success', 'Obra excluída com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('obras.index')->withErrors(['general' => 'Erro ao excluir obra.']);
        }
    }
}
