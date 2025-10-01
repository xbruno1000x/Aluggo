<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use App\Models\Pagamento;
use App\Models\Aluguel;
use Carbon\Carbon;

class PagamentoController extends Controller
{
    public function index(Request $request): View
    {
        $month = $request->query('month', Carbon::now()->startOfMonth()->toDateString());
        // Accept multiple month formats: 'Y-m', 'Y-m-d', 'm/Y' or 'd/m/Y'
        $ref = $this->normalizeMonthToStart($month);
        $refDate = Carbon::parse($ref)->startOfMonth()->toDateString();

        $query = Pagamento::with('aluguel.imovel', 'aluguel.locatario')
            ->whereDate('referencia_mes', $refDate);

        $aluguelFilter = null;
        if ($request->has('aluguel_id')) {
            $aluguelFilter = (int)$request->query('aluguel_id');
            $query->where('aluguel_id', $aluguelFilter);
        }

        // Always ensure pagamentos exist for active alugueis in the requested month.
        // Using firstOrCreate makes this idempotent and avoids racey existence checks.
        $start = Carbon::parse($ref)->startOfMonth();
        $end = Carbon::parse($ref)->endOfMonth();

        $alugueisQuery = Aluguel::whereDate('data_inicio', '<=', $end->toDateString())
            ->where(function ($q) use ($start) {
                $q->whereNull('data_fim')
                  ->orWhereDate('data_fim', '>=', $start->toDateString());
            });

        if ($aluguelFilter) {
            $alugueisQuery->where('id', $aluguelFilter);
        }

        $alugueis = $alugueisQuery->get();
        foreach ($alugueis as $a) {
            // create pagamento only if not exists (unique constraint protects duplicates)
            Pagamento::firstOrCreate([
                'aluguel_id' => $a->id,
                'referencia_mes' => $refDate,
            ], [
                'valor_devido' => $a->valor_mensal ?? 0,
                'valor_recebido' => 0,
                'status' => 'pending',
            ]);
        }

        $pagamentos = $query->paginate(20);
        $pagamentos->appends($request->query());

        // pass normalized month start to the view
        $ref = $refDate;
        return view('pagamentos.index', compact('pagamentos', 'ref'));
    }

    protected function normalizeMonthToStart(string $input): string
    {
        $input = trim($input);
        // format MM/YYYY
        if (preg_match('/^\d{2}\/\d{4}$/', $input)) {
            try {
                $dt = Carbon::createFromFormat('m/Y', $input)->startOfMonth();
                return $dt->toDateString();
            } catch (\Exception $e) {
                // fallthrough
            }
        }

        // format DD/MM/YYYY
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $input)) {
            try {
                $dt = Carbon::createFromFormat('d/m/Y', $input)->startOfMonth();
                return $dt->toDateString();
            } catch (\Exception $e) {
                // fallthrough
            }
        }

        // try generic parse (handles Y-m, Y-m-d, etc.)
        try {
            $dt = Carbon::parse($input)->startOfMonth();
            return $dt->toDateString();
        } catch (\Exception $e) {
            // fallback to now
            return Carbon::now()->startOfMonth()->toDateString();
        }
    }

    public function markPaid(Request $request, Pagamento $pagamento): RedirectResponse
    {
        $data = $request->validate([
            'valor_recebido' => ['required', 'numeric', 'min:0'],
            'observacao' => ['nullable', 'string'],
        ]);

        $pagamento->markPaid((float)$data['valor_recebido'], now(), $data['observacao'] ?? null);

        return redirect()->back()->with('success', 'Pagamento marcado.');
    }

    public function revert(Pagamento $pagamento): RedirectResponse
    {
        // Reverter o pagamento: voltar ao estado pendente e limpar informações de pagamento
        $pagamento->valor_recebido = 0;
        $pagamento->status = 'pending';
        $pagamento->data_pago = null;
        $pagamento->observacao = null;
        $pagamento->save();

        return redirect()->back()->with('success', 'Pagamento revertido.');
    }

    public function renew(Aluguel $aluguel): RedirectResponse
    {
        // renovação simples: estende data_fim por mesmo período (se existir) ou 1 ano se indefinido
        if ($aluguel->data_fim) {
            $start = Carbon::parse($aluguel->data_inicio);
            $end = Carbon::parse($aluguel->data_fim);
            $intervalDays = $start->diffInDays($end);
            $aluguel->data_fim = Carbon::parse($aluguel->data_fim)->addDays($intervalDays);
        } else {
            $aluguel->data_fim = Carbon::parse($aluguel->data_inicio)->addYear();
        }
        $aluguel->save();
        return redirect()->back()->with('success', 'Contrato renovado.');
    }

    public function markAllPaid(Request $request): RedirectResponse
    {
        $month = $request->input('month', Carbon::now()->startOfMonth()->toDateString());
        $ref = $this->normalizeMonthToStart($month);
        $refDate = Carbon::parse($ref)->startOfMonth()->toDateString();

        $aluguelId = $request->input('aluguel_id');

        // Ensure pagamentos exist for the month (lazy creation)
        $start = Carbon::parse($ref)->startOfMonth();
        $end = Carbon::parse($ref)->endOfMonth();

        $alugueisQuery = Aluguel::whereDate('data_inicio', '<=', $end->toDateString())
            ->where(function ($q) use ($start) {
                $q->whereNull('data_fim')
                  ->orWhereDate('data_fim', '>=', $start->toDateString());
            });

        if ($aluguelId) {
            $alugueisQuery->where('id', (int)$aluguelId);
        }

        $alugueis = $alugueisQuery->get();
        foreach ($alugueis as $a) {
            Pagamento::firstOrCreate([
                'aluguel_id' => $a->id,
                'referencia_mes' => $refDate,
            ], [
                'valor_devido' => $a->valor_mensal ?? 0,
                'valor_recebido' => 0,
                'status' => 'pending',
            ]);
        }

        // Mark each pagamento as paid explicitly to avoid edge cases with bulk updates
        $now = now();
        $pagQuery = Pagamento::whereDate('referencia_mes', $refDate)->whereIn('status', ['pending', 'partial']);
        if ($aluguelId) $pagQuery->where('aluguel_id', (int)$aluguelId);

        $pagamentos = $pagQuery->get();
        foreach ($pagamentos as $pag) {
            $pag->valor_recebido = $pag->valor_devido;
            $pag->status = 'paid';
            $pag->data_pago = $now;
            $pag->observacao = null;
            $pag->save();
        }

        return redirect()->back()->with('success', 'Todos os pagamentos marcados como pagos.');
    }
}
