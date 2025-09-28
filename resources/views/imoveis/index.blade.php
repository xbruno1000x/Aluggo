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

<form method="GET" action="{{ route('imoveis.index') }}" class="mb-3">
    <div class="row g-2">
        <div class="col-md-2">
            <input type="text" name="nome" class="form-control" placeholder="Nome do imóvel" value="{{ request('nome') }}">
        </div>

        <div class="col-md-1">
            <input type="text" name="numero" class="form-control" placeholder="Número" value="{{ request('numero') }}">
        </div>

        <div class="col-md-2">
            <select name="tipo" class="form-select">
                <option value="">Todos os tipos</option>
                @foreach(['apartamento','terreno','loja','casa','garagem'] as $tipo)
                    <option value="{{ $tipo }}" {{ request('tipo') === $tipo ? 'selected' : '' }}>{{ ucfirst($tipo) }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2">
            <select name="status" class="form-select">
                <option value="">Todos os status</option>
                @foreach(['disponível','vendido','alugado'] as $status)
                    <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <select name="propriedade_id" class="form-select">
                <option value="">Todas as propriedades</option>
                @foreach($propriedades as $prop)
                    <option value="{{ $prop->id }}" {{ request('propriedade_id') == $prop->id ? 'selected' : '' }}>
                        {{ $prop->nome }} ({{ $prop->bairro }})
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-2 d-flex gap-2">
            <button class="btn btn-outline-danger flex-fill" type="submit">Filtrar</button>
            <a href="{{ route('imoveis.index') }}" class="btn btn-outline-danger flex-fill">Limpar</a>
        </div>
    </div>
</form>

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
                    <td>{{ $imovel->nome }}{{ $imovel->numero ? ' (nº ' . $imovel->numero . ')' : '' }}</td>
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