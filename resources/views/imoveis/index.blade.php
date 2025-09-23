{{-- resources/views/imoveis/index.blade.php --}}
@extends('layouts.app')

@push('scripts-body')
    @vite(['resources/ts/delete-confirm.ts'])
@endpush

@section('title', 'Gestão de Imóveis')
@section('header', 'Gestão de Imóveis')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="text-warning">@yield('header')</h2>
    <a href="{{ route('imoveis.create') }}" class="btn btn-success">Cadastrar Novo Imóvel</a>
</div>

<div class="table-responsive">
    <table class="table table-dark table-striped table-hover align-middle">
        <thead class="table-primary text-dark">
            <tr>
                <th>Nome</th>
                <th>Tipo</th>
                <th>Status</th>
                <th>Valor de Mercado</th>
                <th>Propriedade</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($imoveis as $imovel)
                <tr>
                    <td>{{ $imovel->nome }}</td>
                    <td>{{ ucfirst($imovel->tipo) }}</td>
                    <td>{{ ucfirst($imovel->status) }}</td>
                    <td>
                        {{ $imovel->valor_compra ? 'R$ ' . number_format($imovel->valor_compra, 2, ',', '.') : 'N/A' }}
                    </td>
                    <td>{{ $imovel->propriedade->nome }} ({{ $imovel->propriedade->endereco }}, {{ $imovel->propriedade->bairro }})</td>
                    <td class="d-flex gap-2">
                        <a href="{{ route('imoveis.edit', $imovel) }}" class="btn btn-sm btn-warning">Editar</a>

                        <form action="{{ route('imoveis.destroy', $imovel) }}" 
                            method="POST" 
                            data-confirm 
                            data-confirm-title="Confirmação"
                            data-confirm-text="Deseja realmente excluir o imóvel {{ $imovel->nome }}?">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger">
                                Excluir
                            </button>
                        </form>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

@if($imoveis->isEmpty())
    <p class="text-center text-light mt-4">Nenhum imóvel cadastrado ainda.</p>
@endif
@endsection