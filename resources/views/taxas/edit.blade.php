@extends('layouts.app')

@section('title','Editar Taxa')
@section('header','Editar Taxa')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 600px;">
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>
        <form action="{{ route('taxas.update', $taxa) }}" method="POST" class="row g-3" data-spinner>
            @csrf
            @method('PUT')
            <div class="col-12">
                <label class="form-label">Vincular a</label>
                <div>
                    <!-- <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="assoc_type" id="assoc_none" value="none" {{ empty($taxa->imovel_id) && empty($taxa->propriedade_id) ? 'checked' : '' }}>
                        <label class="form-check-label" for="assoc_none">Nenhum (taxa geral do proprietário)</label>
                    </div> -->
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="assoc_type" id="assoc_imovel" value="imovel" {{ !empty($taxa->imovel_id) ? 'checked' : '' }}>
                        <label class="form-check-label" for="assoc_imovel">Imóvel</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="assoc_type" id="assoc_propriedade" value="propriedade" {{ !empty($taxa->propriedade_id) ? 'checked' : '' }}>
                        <label class="form-check-label" for="assoc_propriedade">Propriedade</label>
                    </div>
                </div>
            </div>

            <div class="col-12 assoc-target assoc-imovel {{ !empty($taxa->imovel_id) ? '' : 'd-none' }}">
                <label class="form-label">Imóvel</label>
                <select name="imovel_id" class="form-select">
                    <option value="">Selecione</option>
                    @foreach($imoveis as $im)
                        <option value="{{ $im->id }}" {{ $taxa->imovel_id == $im->id ? 'selected' : '' }}>{{ $im->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 assoc-target assoc-propriedade {{ !empty($taxa->propriedade_id) ? '' : 'd-none' }}">
                <label class="form-label">Propriedade</label>
                <select name="propriedade_id" class="form-select">
                    <option value="">Selecione</option>
                    @foreach($propriedades as $p)
                        <option value="{{ $p->id }}" {{ $taxa->propriedade_id == $p->id ? 'selected' : '' }}>{{ $p->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="condominio" {{ $taxa->tipo=='condominio' ? 'selected' : '' }}>Condomínio</option>
                    <option value="iptu" {{ $taxa->tipo=='iptu' ? 'selected' : '' }}>IPTU</option>
                    <option value="agua" {{ $taxa->tipo=='agua' ? 'selected' : '' }}>Água</option>
                    <option value="energia" {{ $taxa->tipo=='energia' ? 'selected' : '' }}>Energia</option>
                    <option value="outros" {{ $taxa->tipo=='outros' ? 'selected' : '' }}>Outros</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Pagador</label>
                <select name="pagador" class="form-select">
                    <option value="proprietario" {{ $taxa->pagador=='proprietario' ? 'selected' : '' }}>Proprietário</option>
                    <option value="locatario" {{ $taxa->pagador=='locatario' ? 'selected' : '' }}>Locatário</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Valor</label>
                <input type="number" name="valor" step="0.01" class="form-control" value="{{ $taxa->valor }}" required>
            </div>
            <div class="col-6">
                <label class="form-label">Data de Pagamento</label>
                <input type="date" name="data_pagamento" class="form-control" value="{{ optional($taxa->data_pagamento)->toDateString() }}" required>
            </div>
            <div class="col-12">
                <label class="form-label">Observação</label>
                <textarea name="observacao" class="form-control">{{ $taxa->observacao }}</textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <a href="{{ route('taxas.index') }}" class="btn btn-secondary">Voltar</a>
                <button type="submit" class="btn btn-success ms-auto">
                    <span class="btn-text">Salvar</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>
    </div>
</div>
@push('scripts-body')
    @vite(['resources/ts/taxas-assoc.ts', 'resources/ts/form-spinner.ts'])
@endpush
@endsection
