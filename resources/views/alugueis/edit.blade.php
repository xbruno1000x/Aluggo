@extends('layouts.app')

@section('title', 'Editar Aluguel')
@section('header', 'Editar Aluguel')

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ route('alugueis.update', $aluguel) }}" method="POST" data-spinner>
            @csrf
            @method('PUT')

            <div class="mb-3">
                <label for="imovel_id" class="form-label">Imóvel</label>
                <select name="imovel_id" id="imovel_id" class="form-select @error('imovel_id') is-invalid @enderror" required>
                    <option value="">Selecione um imóvel</option>
                    @foreach($imoveis as $imovel)
                        <option value="{{ $imovel->id }}" {{ (old('imovel_id') ?? $aluguel->imovel_id) == $imovel->id ? 'selected' : '' }}>
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
                        <option value="{{ $locatario->id }}" {{ (old('locatario_id') ?? $aluguel->locatario_id) == $locatario->id ? 'selected' : '' }}>
                            {{ $locatario->nome }}
                        </option>
                    @endforeach
                </select>
                @error('locatario_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="row">
                <div class="col-md-3 mb-3">
                    <label for="valor_mensal" class="form-label">Valor mensal (R$)</label>
                    <input type="number" name="valor_mensal" id="valor_mensal" step="0.01" min="0" class="form-control @error('valor_mensal') is-invalid @enderror" value="{{ old('valor_mensal') ?? $aluguel->valor_mensal }}" required>
                    @error('valor_mensal')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label for="caucao" class="form-label">Caução (R$)</label>
                    <div class="input-group">
                        <input type="number" name="caucao" id="caucao" step="0.01" min="0" class="form-control @error('caucao') is-invalid @enderror" value="{{ old('caucao') ?? $aluguel->caucao }}" placeholder="{{ $aluguel->caucao ? '' : 'N/A' }}">
                        <button type="button" class="btn btn-outline-secondary" id="btn-na-caucao">N/A</button>
                    </div>
                    <div class="form-text">Deixe vazio ou clique em N/A se não houver caução</div>
                    @error('caucao')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label for="data_inicio" class="form-label">Data de início</label>
                    <input type="date" name="data_inicio" id="data_inicio" class="form-control @error('data_inicio') is-invalid @enderror" value="{{ old('data_inicio') ?? ($aluguel->data_inicio ? \Carbon\Carbon::parse($aluguel->data_inicio)->format('Y-m-d') : '') }}" required>
                    @error('data_inicio')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="col-md-3 mb-3">
                    <label for="data_fim" class="form-label">Data de fim (opcional)</label>
                    <input type="date" name="data_fim" id="data_fim" class="form-control @error('data_fim') is-invalid @enderror" value="{{ old('data_fim') ?? ($aluguel->data_fim ? \Carbon\Carbon::parse($aluguel->data_fim)->format('Y-m-d') : '') }}">
                    @error('data_fim')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-success">
                    <span class="btn-text">Salvar alterações</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
                <a href="{{ route('alugueis.index') }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@push('scripts-body')
    @vite(['resources/ts/form-spinner.ts'])
    <script>
        document.getElementById('btn-na-caucao').addEventListener('click', function() {
            const caucaoInput = document.getElementById('caucao');
            caucaoInput.value = '';
            caucaoInput.placeholder = 'N/A';
        });
    </script>
@endpush
@endsection
