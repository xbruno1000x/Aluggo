<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Services\RelatorioService;
use Carbon\Carbon;

class RelatorioController extends Controller
{
    public function index(Request $request, RelatorioService $svc): View
    {
        $imovelId = $request->query('imovel_id') ? (int)$request->query('imovel_id') : null;
        $start = $request->query('start', Carbon::now()->subYear()->startOfMonth()->toDateString());
        $end = $request->query('end', Carbon::now()->endOfMonth()->toDateString());

        $filters = [
            'include_vendas' => $request->query('include_vendas', '1') === '1',
            'include_compras' => $request->query('include_compras', '1') === '1',
            'include_alugueis' => $request->query('include_alugueis', '1') === '1',
            'include_obras' => $request->query('include_obras', '1') === '1',
            'include_taxas' => $request->query('include_taxas', '1') === '1',
        ];

        $data = $svc->getReport($imovelId, $start, $end, Auth::id(), $filters);

        $imoveis = \App\Models\Imovel::whereHas('propriedade', function ($q) {
            $q->where('proprietario_id', Auth::id());
        })->orderBy('nome')->get();

        return view('relatorios.index', compact('data', 'start', 'end', 'imovelId', 'imoveis'));
    }
}
