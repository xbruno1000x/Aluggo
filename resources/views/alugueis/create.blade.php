@extends('layouts.app')

@section('title', 'Cadastrar Aluguel')
@section('header', 'Cadastrar Aluguel')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('alugueis.store') }}" method="POST">
            @csrf

            <div class="mb-3">
                <label for="imovel_id" class="form-label">Imóvel</label>
                <select name="imovel_id" id="imovel_id" class="form-select @error('imovel_id') is-invalid @enderror" required>
                    <option value="">Selecione um imóvel</option>
                    @foreach($imoveis as $imovel)
                        <option value="{{ $imovel->id }}" {{ old('imovel_id') == $imovel->id ? 'selected' : '' }}>
                            {{ $imovel->nome }}{{ $imovel->numero ? ' (nº ' . $imovel->numero . ')' : '' }} — {{ $imovel->propriedade->nome ?? '' }}
                        </option>
                    @endforeach
                </select>
                @error('imovel_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="mb-3">
                <label for="locatario_id" class="form-label">Locatário</label>
                <select name="locatario_id" id="locatario_id" class="form-select @error('locatario_id') is-invalid @enderror" required>
                    <option value="">Selecione um locatário</option>
                    @foreach($locatarios as $locatario)
                        <option value="{{ $locatario->id }}" {{ old('locatario_id') == $locatario->id ? 'selected' : '' }}>
                            {{ $locatario->nome }}
                        </option>
                    @endforeach
                </select>
                @error('locatario_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-4 mb-3">
                    <label for="valor_mensal" class="form-label">Valor mensal (R$)</label>
                    <input type="number" name="valor_mensal" id="valor_mensal" step="0.01" min="0" class="form-control @error('valor_mensal') is-invalid @enderror" value="{{ old('valor_mensal') }}" required>
                    @error('valor_mensal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="data_inicio" class="form-label">Data de início</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control @error('data_inicio') is-invalid @enderror" value="{{ old('data_inicio') }}" required>
                    @error('data_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-4 mb-3">
                    <label for="data_fim" class="form-label">Data de fim (opcional)</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control @error('data_fim') is-invalid @enderror" value="{{ old('data_fim') }}">
                    @error('data_fim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">Salvar</button>
                <a href="{{ route('alugueis.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>
@endsection