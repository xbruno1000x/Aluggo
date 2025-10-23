<?php

namespace App\Http\Controllers;

use App\Models\Aluguel;
use App\Models\Imovel;
use App\Models\Locatario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use App\Services\IgpmService;

class AluguelController extends Controller
{
    /**
     * Lista contratos de aluguel.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        $filterImovelId = $request->input('imovel_id');
        if ($filterImovelId) {
            $imovel = Imovel::find((int) $filterImovelId);
            if ($imovel && strtolower((string) $imovel->status) === 'vendido') {
                return redirect()->back()->with('error', 'Não é possível gerenciar contratos de imóveis vendidos.');
            }
        }

        $alugueis = Aluguel::with(['imovel.propriedade', 'locatario'])
            ->whereHas('imovel', function ($q) {
                $q->whereNull('status')->orWhere('status', '!=', 'vendido');
            })
            ->whereHas('imovel.propriedade', function ($q) use ($proprietarioId) {
                $q->where('proprietario_id', $proprietarioId);
            })
            ->orderByDesc('data_inicio')
            ->paginate(15);

        return view('alugueis.index', compact('alugueis'));
    }

    /**
     * Mostrar formulário de criação de aluguel.
     */
    public function create(): View
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        $imoveis = Imovel::whereHas('propriedade', function ($q) use ($proprietarioId) {
                $q->where('proprietario_id', $proprietarioId);
            })
            ->orderBy('nome')
            ->get();
            
        $locatarios = Locatario::where('proprietario_id', $proprietarioId)
            ->orderBy('nome')
            ->get();

        return view('alugueis.create', compact('imoveis', 'locatarios'));
    }

    /**
     * Mostrar formulário de edição de aluguel.
     */
    public function edit(Aluguel $aluguel): View
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        $imoveis = Imovel::whereHas('propriedade', function ($q) use ($proprietarioId) {
                $q->where('proprietario_id', $proprietarioId);
            })
            ->orderBy('nome')
            ->get();
            
        $locatarios = Locatario::where('proprietario_id', $proprietarioId)
            ->orderBy('nome')
            ->get();

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
            'caucao' => ['nullable', 'numeric', 'min:0'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'imovel_id' => ['required', 'exists:imoveis,id'],
            'locatario_id' => ['required', 'exists:locatarios,id'],
        ]);

        $imovelId = (int) $data['imovel_id'];
        $newStart = Carbon::parse($data['data_inicio'])->startOfDay();
        $newEnd = isset($data['data_fim']) ? Carbon::parse($data['data_fim'])->endOfDay() : Carbon::createFromDate(9999, 12, 31)->endOfDay();

        $overlap = Aluguel::where('imovel_id', $imovelId)
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
            $aluguel = Aluguel::create($data);

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
            'caucao' => ['nullable', 'numeric', 'min:0'],
            'data_inicio' => ['required', 'date'],
            'data_fim' => ['nullable', 'date', 'after_or_equal:data_inicio'],
            'imovel_id' => ['required', 'exists:imoveis,id'],
            'locatario_id' => ['required', 'exists:locatarios,id'],
        ]);

        $imovelId = (int) $data['imovel_id'];
        $newStart = Carbon::parse($data['data_inicio'])->startOfDay();
        $newEnd = isset($data['data_fim']) ? Carbon::parse($data['data_fim'])->endOfDay() : Carbon::createFromDate(9999, 12, 31)->endOfDay();

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

    /**
     * Reajusta o valor do aluguel manualmente ou via IGP-M acumulado.
     */
    public function adjust(Request $request, Aluguel $aluguel, IgpmService $igpmService): JsonResponse
    {
        $mode = (string) $request->input('mode');
        $preview = $request->boolean('preview');

        if (!in_array($mode, ['manual', 'igpm'], true)) {
            return response()->json([
                'message' => 'Modo de reajuste inválido.',
            ], 422);
        }

        $newValue = null;
        $igpmPercent = null;
        $period = null;

        if ($mode === 'manual') {
            $raw = (string) $request->input('novo_valor', '');
            $normalized = $this->normalizeDecimal($raw);
            if ($normalized === null) {
                return response()->json([
                    'message' => 'Informe um valor válido para o reajuste manual.',
                    'errors' => ['novo_valor' => ['Informe um valor válido para o reajuste manual.']],
                ], 422);
            }

            if ($normalized < 0) {
                return response()->json([
                    'message' => 'O valor do aluguel não pode ser negativo.',
                    'errors' => ['novo_valor' => ['O valor do aluguel não pode ser negativo.']],
                ], 422);
            }

            $newValue = round($normalized, 2);
        } else {
            $today = Carbon::today();
            $contractStart = Carbon::parse($aluguel->data_inicio ?? $today->toDateString());
            $fromCandidate = $today->copy()->subYear();
            $from = $contractStart->greaterThan($fromCandidate) ? $contractStart->copy() : $fromCandidate;
            $from->startOfMonth();

            $to = $today->copy()->endOfMonth();
            if ($aluguel->data_fim) {
                $contractEnd = Carbon::parse($aluguel->data_fim)->endOfMonth();
                if ($contractEnd->lessThan($to)) {
                    $to = $contractEnd;
                }
            }

            if ($to->lessThan($from)) {
                $to = $from->copy();
            }

            try {
                $result = $igpmService->accumulatedPercent($from, $to);
            } catch (\Throwable $e) {
                return response()->json([
                    'message' => 'Falha ao obter o IGP-M. Tente novamente mais tarde.',
                ], 502);
            }

            $igpmPercent = $result['percent'];
            $period = [
                'start' => $result['from']->toDateString(),
                'end' => $result['to']->toDateString(),
                'start_br' => $result['from']->format('d/m/Y'),
                'end_br' => $result['to']->format('d/m/Y'),
            ];

            $currentValue = (float) $aluguel->valor_mensal;
            $newValue = round($currentValue * (1 + ($igpmPercent / 100)), 2);
        }

        if (!$preview) {
            $aluguel->valor_mensal = $newValue;
            $aluguel->save();
        }

        return response()->json([
            'ok' => true,
            'preview' => $preview,
            'mode' => $mode,
            'new_value' => $newValue,
            'new_value_formatted' => 'R$ ' . number_format($newValue, 2, ',', '.'),
            'igpm_percent' => $igpmPercent,
            'period' => $period,
            'message' => $preview ? 'Simulação concluída.' : 'Aluguel reajustado com sucesso.',
        ]);
    }

    private function normalizeDecimal(string $raw): ?float
    {
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return null;
        }

        $normalized = preg_replace('/\.(?=\d{3}(?:[^\d]|$))/', '', $trimmed) ?? $trimmed;
        $normalized = str_replace(',', '.', $normalized);

        if (!is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * Encerra o contrato de aluguel definindo data_fim para hoje.
     */
    public function terminate(Aluguel $aluguel): RedirectResponse
    {
        $proprietarioId = \Illuminate\Support\Facades\Auth::id();
        
        if (!$aluguel->imovel || 
            !$aluguel->imovel->propriedade || 
            $aluguel->imovel->propriedade->proprietario_id !== $proprietarioId) {
            abort(403, 'Acesso negado.');
        }

        if ($aluguel->data_fim && \Carbon\Carbon::parse($aluguel->data_fim)->lt(\Carbon\Carbon::today())) {
            return redirect()->route('alugueis.index')->with('info', 'Este contrato já está encerrado.');
        }

        \Illuminate\Support\Facades\DB::beginTransaction();
        try {
            $aluguel->data_fim = \Carbon\Carbon::today();
            $aluguel->save();
            $aluguel->imovel->status = 'disponivel';
            $aluguel->imovel->save();

            \Illuminate\Support\Facades\DB::commit();
            return redirect()->route('alugueis.index')->with('success', 'Contrato encerrado com sucesso.');
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            return redirect()->route('alugueis.index')->with('error', 'Erro ao encerrar contrato.');
        }
    }
}
