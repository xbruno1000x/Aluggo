{{-- resources/views/imoveis/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar Imóvel')
@section('header', 'Editar Imóvel')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 600px;">
        <form action="{{ route('imoveis.update', $imovel) }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-12">
                <label for="nome" class="form-label">Nome:</label>
                <input type="text" id="nome" name="nome" value="{{ old('nome', $imovel->nome) }}" required class="form-control">
            </div>

            <div class="col-md-6">
                <label for="tipo" class="form-label">Tipo:</label>
                <select id="tipo" name="tipo" required class="form-select">
                    <option value="apartamento" {{ $imovel->tipo === 'apartamento' ? 'selected' : '' }}>Apartamento</option>
                    <option value="terreno" {{ $imovel->tipo === 'terreno' ? 'selected' : '' }}>Terreno</option>
                    <option value="loja" {{ $imovel->tipo === 'loja' ? 'selected' : '' }}>Loja</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="status" class="form-label">Status:</label>
                <select id="status" name="status" required class="form-select">
                    <option value="disponível" {{ $imovel->status === 'disponível' ? 'selected' : '' }}>Disponível</option>
                    <option value="vendido" {{ $imovel->status === 'vendido' ? 'selected' : '' }}>Vendido</option>
                    <option value="alugado" {{ $imovel->status === 'alugado' ? 'selected' : '' }}>Alugado</option>
                </select>
            </div>

            <div class="col-md-6">
                <label for="valor_compra" class="form-label">Valor da Compra:</label>
                <input type="number" step="0.01" id="valor_compra" name="valor_compra" value="{{ old('valor_compra', $imovel->valor_compra) }}" class="form-control">
            </div>

            <div class="col-md-6">
                <label for="data_aquisicao" class="form-label">Data de Aquisição:</label>
                <input type="date" id="data_aquisicao" name="data_aquisicao" value="{{ old('data_aquisicao', $imovel->data_aquisicao) }}" class="form-control">
            </div>

            <div class="col-12">
                <label for="propriedade_id" class="form-label">Propriedade:</label>
                <select id="propriedade_id" name="propriedade_id" required class="form-select">
                    @foreach ($propriedades as $propriedade)
                        <option value="{{ $propriedade->id }}" {{ $imovel->propriedade_id === $propriedade->id ? 'selected' : '' }}>
                            {{ $propriedade->nome }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">Salvar Alterações</button>
            </div>
        </form>
    </div>
</div>
@endsection