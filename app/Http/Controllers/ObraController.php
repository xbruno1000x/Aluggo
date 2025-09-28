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
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $obras = Obra::with('imovel.propriedade')->orderByDesc('data_inicio')->paginate(15);

        return view('obras.index', compact('obras'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $imoveis = Imovel::orderBy('nome')->get();
        return view('obras.create', compact('imoveis'));
    }

    /**
     * Store a newly created resource in storage.
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
     * Show the form for editing the specified resource.
     */
    public function edit(Obra $obra): View
    {
        $imoveis = Imovel::orderBy('nome')->get();
        return view('obras.edit', compact('obra', 'imoveis'));
    }

    /**
     * Update the specified resource in storage.
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
     * Remove the specified resource from storage.
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
