@extends('layouts.app')

@section('title', 'Transação #' . $transacao->id)
@section('header', 'Detalhes da Transação')

@section('content')
    <div class="w-100" style="max-width: 800px;">
        <h2 class="mb-4 text-warning">@yield('header')</h2>

        <div class="card bg-secondary text-light mb-3">
            <div class="card-body">
                <h5 class="card-title">Transação #{{ $transacao->id }}</h5>
                <p class="card-text">Imóvel: {{ $transacao->imovel->nome ?? '-' }}{{ $transacao->imovel && $transacao->imovel->numero ? ' (nº ' . $transacao->imovel->numero . ')' : '' }}</p>
                <p class="card-text">Valor: R$ {{ number_format($transacao->valor_venda, 2, ',', '.') }}</p>
                <p class="card-text">Data: {{ $transacao->data_venda ? $transacao->data_venda->format('d/m/Y') : '-' }}</p>
            </div>
        </div>

        <div class="text-center">
            <a href="{{ route('transacoes.index') }}" class="btn btn-secondary">Voltar</a>
        </div>
    </div>
@endsection
