<?php

namespace App\Http\Controllers;

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use App\Services\IgpmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

class AluguelController extends Controller
{
    /**
     * Lista contratos de aluguel.
     */
    public function index(Request $request): View
    {
        $alugueis = Aluguel::with(['imovel.propriedade', 'locatario'])
            ->orderByDesc('data_inicio')
            ->paginate(15);

        return view('alugueis.index', compact('alugueis'));
    }

    /**
     * Mostrar formulário de criação de aluguel.
     */
    public function create(): View
    {
        $imoveis = Imovel::orderBy('nome')->get();
        $locatarios = Locatario::orderBy('nome')->get();

        return view('alugueis.create', compact('imoveis', 'locatarios'));
    }

    /**
     * Mostrar formulário de edição de aluguel.
     */
    public function edit(Aluguel $aluguel): View
    {
        $imoveis = Imovel::orderBy('nome')->get();
        $locatarios = Locatario::orderBy('nome')->get();

        return view('alugueis.edit', compact('aluguel', 'imoveis', 'locatarios'));
    }

    /**
     * Persistir novo aluguel.
     *
     * Regras adicionais:
     * - Não permite contratos que se sobreponham no mesmo imóvel.
     * - Atualiza status do imóvel para 'alugado' se o contrato estiver ativo hoje.
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'valor_mensal' => ['required', 'numeric', 'min:0'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'imovel_id' => ['required', 'exists:imoveis,id'],
            'locatario_id' => ['required', 'exists:locatarios,id'],
        ]);

        $imovelId = (int) $data['imovel_id'];
        $newStart = Carbon::parse($data['data_inicio'])->startOfDay();
        $newEnd = isset($data['data_fim']) ? Carbon::parse($data['data_fim'])->endOfDay() : Carbon::createFromDate(9999, 12, 31)->endOfDay();

        // verificar sobreposição de contratos no mesmo imóvel
        $overlap = Aluguel::where('imovel_id', $imovelId)
            ->where(function ($q) use ($newStart, $newEnd) {
                // contratos sem data_fim que começam antes do fim do novo contrato
                $q->whereNull('data_fim')
                    ->where('data_inicio', '<=', $newEnd->toDateString());
                // ou contratos com data_fim que se intersectam [data_inicio, data_fim] com [newStart, newEnd]
                $q->orWhere(function ($q2) use ($newStart, $newEnd) {
                    $q2->whereNotNull('data_fim')
                        ->where('data_inicio', '<=', $newEnd->toDateString())
                        ->where('data_fim', '>=', $newStart->toDateString());
                });
            })
            ->exists();

        if ($overlap) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['imovel_id' => 'Já existe um contrato para este imóvel que se sobrepõe ao período informado.']);
        }

        DB::beginTransaction();
        try {
            $aluguel = Aluguel::create($data);

            // Atualizar status do imóvel se o contrato estiver ativo hoje
            $today = Carbon::today()->startOfDay();
            $isActiveNow = ($newStart->lte($today) && $today->lte($newEnd));

            if ($isActiveNow) {
                $imovel = Imovel::find($imovelId);
                if ($imovel) {
                    $imovel->status = 'alugado';
                    $imovel->save();
                }
            }

            DB::commit();

            return redirect()->route('alugueis.index')->with('success', 'Aluguel cadastrado com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['general' => 'Erro ao salvar contrato. Tente novamente.']);
        }
    }

    /**
     * Atualiza um contrato existente.
     */
    public function update(Request $request, Aluguel $aluguel): RedirectResponse
    {
        $data = $request->validate([
            'valor_mensal' => ['required', 'numeric', 'min:0'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'imovel_id' => ['required', 'exists:imoveis,id'],
            'locatario_id' => ['required', 'exists:locatarios,id'],
        ]);

        $imovelId = (int) $data['imovel_id'];
        $newStart = Carbon::parse($data['data_inicio'])->startOfDay();
        $newEnd = isset($data['data_fim']) ? Carbon::parse($data['data_fim'])->endOfDay() : Carbon::createFromDate(9999, 12, 31)->endOfDay();

        // verificar sobreposição de contratos no mesmo imóvel, excluindo o contrato atual
        $overlap = Aluguel::where('imovel_id', $imovelId)
            ->where('id', '!=', $aluguel->id)
            ->where(function ($q) use ($newStart, $newEnd) {
                $q->whereNull('data_fim')
                    ->where('data_inicio', '<=', $newEnd->toDateString());
                $q->orWhere(function ($q2) use ($newStart, $newEnd) {
                    $q2->whereNotNull('data_fim')
                        ->where('data_inicio', '<=', $newEnd->toDateString())
                        ->where('data_fim', '>=', $newStart->toDateString());
                });
            })
            ->exists();

        if ($overlap) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['imovel_id' => 'Já existe um contrato para este imóvel que se sobrepõe ao período informado.']);
        }

        DB::beginTransaction();
        try {
            $previousImovelId = $aluguel->getOriginal('imovel_id');

            $aluguel->fill($data);
            $aluguel->save();

            $today = Carbon::today()->startOfDay();
            $isActiveNow = ($newStart->lte($today) && $today->lte($newEnd));

            if ($aluguel->wasChanged('imovel_id')) {
                if ($previousImovelId) {
                    $hasActive = Aluguel::where('imovel_id', $previousImovelId)
                        ->where('data_inicio', '<=', $today->toDateString())
                        ->where(function ($q) use ($today) {
                            $q->whereNull('data_fim')
                              ->orWhere('data_fim', '>=', $today->toDateString());
                        })
                        ->exists();

                    if (! $hasActive) {
                        $im = Imovel::find($previousImovelId);
                        if ($im) {
                            $im->status = 'disponivel';
                            $im->save();
                        }
                    }
                }
            }

            if ($isActiveNow) {
                $im = Imovel::find($imovelId);
                if ($im) {
                    $im->status = 'alugado';
                    $im->save();
                }
            }

            DB::commit();

            return redirect()->route('alugueis.index')->with('success', 'Aluguel atualizado com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->withErrors(['general' => 'Erro ao atualizar contrato. Tente novamente.']);
        }
    }

    /**
     * Remove um contrato de aluguel.
     * Após exclusão, atualiza status do imóvel para 'disponivel' caso não exista
     * outro contrato ativo cobrindo a data atual.
     */
    public function destroy(Aluguel $aluguel): RedirectResponse
    {
        DB::beginTransaction();
        try {
            $aluguelId = $aluguel->id;
            $imovelId = $aluguel->imovel_id;

            $deleted = Aluguel::destroy($aluguelId);

            if (! $deleted) {
                DB::table('alugueis')->where('id', $aluguelId)->delete();
            }

            $stillExists = DB::table('alugueis')->where('id', $aluguelId)->exists();

            if ($stillExists) {
                DB::rollBack();
                return redirect()->route('alugueis.index')->with('error', 'Falha ao excluir o contrato. Tente novamente.');
            }

            $today = Carbon::today()->toDateString();

            $hasActive = Aluguel::where('imovel_id', $imovelId)
                ->where('data_inicio', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('data_fim')
                      ->orWhere('data_fim', '>=', $today);
                })
                ->exists();

            if (! $hasActive) {
                $im = Imovel::find($imovelId);
                if ($im) {
                    $im->status = 'disponivel';
                    $im->save();
                }
            }

            DB::commit();

            return redirect()->route('alugueis.index')->with('success', 'Contrato de aluguel excluído com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('alugueis.index')->with('error', 'Falha ao excluir o contrato. Tente novamente.');
        }
    }

}