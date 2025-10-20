@extends('layouts.app')

@section('title', 'Editar Venda')
@section('header', 'Editar Transação')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 600px;">
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>

        <form action="{{ route('transacoes.update', $transacao) }}" method="post" class="row g-3" data-spinner>
            @csrf
            @method('PUT')

            <div class="col-12">
                <label for="imovel_id" class="form-label">Imóvel</label>
                <select id="imovel_id" name="imovel_id" class="form-select" required>
                    @foreach($imoveis as $imovel)
                        <option value="{{ $imovel->id }}" @if($imovel->id === $transacao->imovel_id) selected @endif>{{ $imovel->nome }}{{ $imovel->numero ? ' (nº ' . $imovel->numero . ')' : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label for="valor_venda" class="form-label">Valor da Venda</label>
                <input id="valor_venda" type="number" step="0.01" min="0" name="valor_venda" class="form-control" value="{{ old('valor_venda', $transacao->valor_venda) }}" required>
            </div>

            <div class="col-12">
                <label for="data_venda" class="form-label">Data da Venda</label>
                <input id="data_venda" type="date" name="data_venda" class="form-control" value="{{ old('data_venda', $transacao->data_venda ? $transacao->data_venda->toDateString() : '') }}" required>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">
                    <span class="btn-text">Salvar</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>

        <div class="mt-3 text-center">
            <a href="{{ route('transacoes.index') }}" class="btn btn-secondary">Voltar para Lista</a>
        </div>
    </div>
</div>

@push('scripts-body')
    @vite(['resources/ts/form-spinner.ts'])
@endpush
@endsection
