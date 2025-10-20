@extends('layouts.app')

@section('title', 'Cadastrar Obra')
@section('header', 'Cadastrar Obra')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('obras.store') }}" method="POST" data-spinner>
            @csrf

            <div class="mb-3">
                <label for="descricao" class="form-label">Descrição</label>
                <textarea name="descricao" id="descricao" rows="4" class="form-control @error('descricao') is-invalid @enderror" required>{{ old('descricao') }}</textarea>
                @error('descricao')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="valor" class="form-label">Valor (R$)</label>
                    <input type="number" name="valor" id="valor" step="0.01" min="0" class="form-control @error('valor') is-invalid @enderror" value="{{ old('valor') }}" required>
                    @error('valor')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="data_inicio" class="form-label">Data de Início</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control @error('data_inicio') is-invalid @enderror" value="{{ old('data_inicio') }}">
                    @error('data_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="data_fim" class="form-label">Data de Fim (Opcional)</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control @error('data_fim') is-invalid @enderror" value="{{ old('data_fim') }}">
                    @error('data_fim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="mb-3">
                <label for="imovel_id" class="form-label">Imóvel</label>
                <select name="imovel_id" id="imovel_id" class="form-select @error('imovel_id') is-invalid @enderror" required>
                    <option value="">Selecione um imóvel</option>
                    @foreach($imoveis as $imovel)
                        <option value="{{ $imovel->id }}" {{ old('imovel_id') == $imovel->id ? 'selected' : '' }}>{{ $imovel->nome }}{{ $imovel->numero ? ' (nº ' . $imovel->numero . ')' : '' }} — {{ $imovel->propriedade->nome ?? '' }}</option>
                    @endforeach
                </select>
                @error('imovel_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <span class="btn-text">Salvar</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <a href="{{ route('obras.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
 </div>

@push('scripts-body')
    @vite(['resources/ts/form-spinner.ts'])
@endpush
@endsection
