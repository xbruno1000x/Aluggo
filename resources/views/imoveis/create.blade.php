{{-- resources/views/imoveis/create.blade.php --}}
@extends('layouts.app')

@section('title', 'Cadastrar Novo Imóvel')
@section('header', 'Cadastrar Novo Imóvel')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 500px;">

        <!-- Título centralizado -->
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>

        <!-- Formulário centralizado -->
        <form action="{{ route('imoveis.store') }}" method="POST" class="row g-3">
            @csrf

            <div class="col-12">
                <label for="nome" class="form-label">Nome:</label>
                <input type="text" id="nome" name="nome" required class="form-control">
            </div>

            <div class="col-12">
                <label for="tipo" class="form-label">Tipo:</label>
                <select id="tipo" name="tipo" required class="form-select">
                    <option value="apartamento">Apartamento</option>
                    <option value="terreno">Terreno</option>
                    <option value="loja">Loja</option>
                </select>
            </div>

            <div class="col-12">
                <label for="status" class="form-label">Status:</label>
                <select id="status" name="status" required class="form-select">
                    <option value="disponível">Disponível</option>
                    <option value="vendido">Vendido</option>
                    <option value="alugado">Alugado</option>
                </select>
            </div>

            <div class="col-12">
                <label for="valor_compra" class="form-label">Valor de Compra:</label>
                <input type="number" step="1000" min="0" id="valor_compra" name="valor_compra" class="form-control">
            </div>

            <div class="col-12">
                <label for="data_aquisicao" class="form-label">Data de Aquisição:</label>
                <input type="date" id="data_aquisicao" name="data_aquisicao" required class="form-control">
            </div>

            <div class="col-12">
                <label for="propriedade_id" class="form-label">Propriedade:</label>
                <select id="propriedade_id" name="propriedade_id" required class="form-select">
                    @foreach($propriedades as $propriedade)
                        <option value="{{ $propriedade->id }}">{{ $propriedade->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">Cadastrar</button>
            </div>
        </form>

        <!-- Botão Voltar -->
        <div class="mt-3 text-center">
            <a href="{{ route('imoveis.index') }}" class="btn btn-secondary">Voltar para Lista</a>
        </div>

    </div>
</div>
@endsection