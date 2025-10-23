@extends('layouts.app')

@section('title', 'Simular Venda - ' . $imovel->nome)
@section('header', 'Simulação de Venda')

@section('content')
<div class="w-100 d-flex justify-content-center">
    <div style="max-width: 600px; width:100%;">
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>

        <div class="card bg-secondary text-light mb-3">
            <div class="card-body">
                <h5 class="card-title">{{ $imovel->nome ?? 'Imóvel sem nome' }}</h5>
                @if($imovel->numero)
                    <p class="card-text">Número: {{ $imovel->numero }}</p>
                @endif
                @if($imovel->propriedade)
                    <p class="card-text">Propriedade: {{ $imovel->propriedade->nome ?? '-' }}</p>
                @endif
                @if($imovel->valor_compra)
                    <p class="card-text">Valor de Aquisição: <span class="text-dolar">R$ {{ number_format($imovel->valor_compra, 2, ',', '.') }}</span></p>
                @endif
                @if($imovel->data_aquisicao)
                    <p class="card-text">Data de Aquisição: {{ \Carbon\Carbon::parse($imovel->data_aquisicao)->format('d/m/Y') }}</p>
                @endif
            </div>
        </div>

        <div class="card bg-dark border-danger text-light">
            <div class="card-header bg-secondary">
                <h5 class="mb-0">Dados da Simulação</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('imoveis.simular-venda.post', $imovel) }}" data-spinner>
                    @csrf

                    <div class="mb-3">
                        <label for="valor_venda" class="form-label">Valor de Venda Simulado (R$) *</label>
                        <input 
                            type="number" 
                            step="0.01" 
                            class="form-control @error('valor_venda') is-invalid @enderror" 
                            id="valor_venda" 
                            name="valor_venda" 
                            value="{{ old('valor_venda', number_format($valorSugerido, 2, '.', '')) }}" 
                            required
                        >
                        @error('valor_venda')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        @if($valorSugerido > 0)
                            <div class="form-text text-light">
                                Valor sugerido baseado no histórico do imóvel
                            </div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="data_venda" class="form-label">Data da Venda Simulada *</label>
                        <input 
                            type="date" 
                            class="form-control @error('data_venda') is-invalid @enderror" 
                            id="data_venda" 
                            name="data_venda" 
                            value="{{ old('data_venda', now()->format('Y-m-d')) }}" 
                            required
                        >
                        @error('data_venda')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        Esta é apenas uma simulação. Nenhum dado será salvo no sistema.
                        Você poderá visualizar todas as métricas financeiras e comparativos.
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="{{ route('imoveis.index') }}" class="btn btn-secondary">Cancelar</a>
                        <button type="submit" class="btn btn-warning">
                            <span class="btn-text">
                                <i class="bi bi-calculator"></i> Simular Venda
                            </span>
                            <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts-body')
    @vite(['resources/ts/form-spinner.ts'])
@endpush
@endsection
