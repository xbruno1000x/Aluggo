@extends('layouts.app')

@section('title', 'Transação #' . $transacao->id)
@section('header', 'Detalhes da Transação')

@section('content')
<div class="w-100 d-flex justify-content-center">
    <div style="max-width: 800px; width:100%;">
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>

        <div class="card bg-secondary text-light mb-3">
            <div class="card-body">
                <h5 class="card-title">Transação #{{ $transacao->id }}</h5>
                <p class="card-text">Imóvel: {{ $transacao->imovel->nome ?? '-' }}{{ $transacao->imovel && $transacao->imovel->numero ? ' (nº ' . $transacao->imovel->numero . ')' : '' }}</p>
                <p class="card-text">Valor de Aquisição: {{ $transacao->imovel && $transacao->imovel->valor_compra ? 'R$ ' . number_format($transacao->imovel->valor_compra, 2, ',', '.') : '-' }}</p>
                <p class="card-text">Valor: R$ {{ number_format($transacao->valor_venda, 2, ',', '.') }}</p>
                <p class="card-text">Data: {{ $transacao->data_venda ? $transacao->data_venda->format('d/m/Y') : '-' }}</p>
            </div>
            <div class="card-footer bg-dark text-light">
                <div class="d-flex justify-content-between align-items-start flex-wrap">
                    <div class="me-3 text-center mb-2">
                        <strong>Saldo de Lucro:</strong>
                        @if(isset($lucro) && $lucro !== null)
                            <div><span class="{{ $lucro >= 0 ? 'text-dolar' : 'text-danger' }}">R$ {{ number_format($lucro, 2, ',', '.') }}</span></div>
                        @else
                            <div>-</div>
                        @endif
                    </div>

                    <div class="mb-2 text-center">
                        <strong>Retorno sobre o Investimento (ROI):</strong>
                        @if(isset($porcentagem) && $porcentagem !== null)
                            <div><span class="{{ $porcentagem >= 0 ? 'text-dolar' : 'text-danger' }}">{{ number_format($porcentagem, 2, ',', '.') }}%</span></div>
                        @else
                            <div>-</div>
                        @endif
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-start flex-wrap mt-3">
                    <div class="me-3 text-center mb-2">
                        <strong>Lucros com Aluguel:</strong>
                        @if(isset($rentalIncome) && $rentalIncome !== null)
                            <div><span class="{{ $rentalIncome >= 0 ? 'text-dolar' : 'text-danger' }}">R$ {{ number_format($rentalIncome, 2, ',', '.') }}</span></div>
                        @else
                            <div>-</div>
                        @endif
                    </div>

                    <div class="me-3 text-center mb-2">
                        <strong>Gastos com Obras:</strong>
                        @if(isset($obraExpenses) && $obraExpenses !== null)
                            <div><span class="{{ $obraExpenses > 0 ? 'text-danger' : 'text-dolar' }}">R$ {{ number_format($obraExpenses, 2, ',', '.') }}</span></div>
                        @else
                            <div>-</div>
                        @endif
                    </div>

                    <div class="text-center mb-2">
                        <strong>Lucro Ajustado:</strong>
                        @if(isset($adjustedProfit) && $adjustedProfit !== null)
                            <div><span class="{{ $adjustedProfit >= 0 ? 'text-dolar' : 'text-danger' }}">R$ {{ number_format($adjustedProfit, 2, ',', '.') }}</span></div>
                            <div class="small">(venda + alugueis - obras)</div>
                        @else
                            <div>-</div>
                        @endif
                    </div>
                </div>

                <div class="mt-2 text-center d-flex flex-column gap-1">
                    @if(!empty($periodText))
                        <div class="small">Período de posse: {{ $periodText }}</div>
                    @endif

                    @if(!empty($selicText))
                        <div class="small">{{ $selicText }}</div>
                    @endif

                    @if(!empty($ipcaText))
                        <div class="small">{{ $ipcaText }}</div>
                    @endif
                </div>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('transacoes.index') }}" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
</div>
@endsection