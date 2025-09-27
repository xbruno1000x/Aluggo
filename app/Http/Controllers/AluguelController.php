<?php

namespace App\Http\Controllers;

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AluguelController extends Controller
{
    /**
     * Lista contratos de aluguel.
     */
    public function index(Request $request)
    {
        $alugueis = Aluguel::with(['imovel.propriedade', 'locatario'])
            ->orderByDesc('data_inicio')
            ->paginate(15);

        return view('alugueis.index', compact('alugueis'));
    }

    /**
     * Mostrar formulário de criação de aluguel.
     */
    public function create()
    {
        $imoveis = Imovel::orderBy('nome')->get();
        $locatarios = Locatario::orderBy('nome')->get();

        return view('alugueis.create', compact('imoveis', 'locatarios'));
    }

    /**
     * Persistir novo aluguel.
     *
     * Regras adicionais:
     * - Não permite contratos que se sobreponham no mesmo imóvel.
     * - Atualiza status do imóvel para 'alugado' se o contrato estiver ativo hoje.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'valor_mensal' => ['required', 'numeric', 'min:0'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'imovel_id' => ['required', 'exists:imoveis,id'],
            'locatario_id' => ['required', 'exists:locatarios,id'],
        ]);

        $imovelId = $data['imovel_id'];
        $newStart = Carbon::parse($data['data_inicio'])->startOfDay();
        $newEnd = isset($data['data_fim']) ? Carbon::parse($data['data_fim'])->endOfDay() : Carbon::createFromDate(9999, 12, 31)->endOfDay();

        // verificar sobreposição de contratos no mesmo imóvel
        $overlap = Aluguel::where('imovel_id', $imovelId)
            ->where(function ($q) use ($newStart, $newEnd) {
                // contratos sem data_fim (abertos) que começam antes do fim do novo contrato
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
            $today = Carbon::today();
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
     * Remove um contrato de aluguel.
     * Após exclusão, atualiza status do imóvel para 'disponível' caso não exista
     * outro contrato ativo cobrindo a data atual.
     */
    public function destroy(Aluguel $aluguel)
    {
        DB::beginTransaction();
        try {
            $imovel = $aluguel->imovel; // relação existente no modelo
            $aluguel->delete();

            // verificar se existe outro contrato ativo para este imóvel hoje
            $today = Carbon::today()->toDateString();

            $hasActive = Aluguel::where('imovel_id', $imovel->id)
                ->where('data_inicio', '<=', $today)
                ->where(function ($q) use ($today) {
                    $q->whereNull('data_fim')
                      ->orWhere('data_fim', '>=', $today);
                })
                ->exists();

            if (! $hasActive) {
                $imovel->status = 'disponível';
                $imovel->save();
            }

            DB::commit();

            return redirect()->route('alugueis.index')->with('success', 'Contrato de aluguel excluído com sucesso.');
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->route('alugueis.index')->with('error', 'Falha ao excluir o contrato. Tente novamente.');
        }
    }
}