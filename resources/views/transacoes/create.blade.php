@extends('layouts.app')

@section('title', 'Registrar Venda')
@section('header', 'Registrar Venda')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 600px;">
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>

        <form action="{{ route('transacoes.store') }}" method="post" class="row g-3" data-spinner>
            @csrf

            <div class="col-12">
                <label for="imovel_id" class="form-label">Imóvel</label>
                <select id="imovel_id" name="imovel_id" class="form-select" required>
                    @foreach($imoveis as $imovel)
                        <option value="{{ $imovel->id }}" {{ request('imovel_id') == $imovel->id ? 'selected' : '' }}>
                            {{ $imovel->nome }}{{ $imovel->numero ? ' (nº ' . $imovel->numero . ')' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <label for="valor_venda" class="form-label">Valor da Venda</label>
                <input type="number" step="0.01" min="0" id="valor_venda" name="valor_venda" class="form-control" value="{{ request('valor', '') }}" required>
                @if(request('valor'))
                    <div class="form-text text-light">
                        <i class="bi bi-info-circle"></i> Valor pré-preenchido da simulação
                    </div>
                @endif
            </div>

            <div class="col-12">
                <label for="data_venda" class="form-label">Data da Venda</label>
                <input type="date" id="data_venda" name="data_venda" class="form-control" value="{{ request('data', now()->format('Y-m-d')) }}" required>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">
                    <span class="btn-text">Registrar Venda</span>
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
