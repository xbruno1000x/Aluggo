@extends('layouts.app')

@section('title','Nova Taxa')
@section('header','Nova Taxa')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 600px;">
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>
        <form action="{{ route('taxas.store') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-12">
                <label class="form-label">Vincular a</label>
                <div>
                    <!-- <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="assoc_type" id="assoc_none" value="none" checked>
                        <label class="form-check-label" for="assoc_none">Nenhum (taxa geral do proprietário)</label>
                    </div> -->
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="assoc_type" id="assoc_imovel" value="imovel" checked>
                        <label class="form-check-label" for="assoc_imovel">Imóvel</label>
                    </div>
                    <div class="form-check form-check-inline">
                        <input class="form-check-input" type="radio" name="assoc_type" id="assoc_propriedade" value="propriedade">
                        <label class="form-check-label" for="assoc_propriedade">Propriedade</label>
                    </div>
                </div>
            </div>

            <div class="col-12 assoc-target assoc-imovel d-none">
                <label class="form-label">Imóvel</label>
                <select name="imovel_id" class="form-select">
                    <option value="">Selecione</option>
                    @foreach($imoveis as $im)
                        <option value="{{ $im->id }}">{{ $im->nome }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-12 assoc-target assoc-propriedade d-none">
                <label class="form-label">Propriedade</label>
                <select name="propriedade_id" class="form-select">
                    <option value="">Selecione</option>
                    @foreach($propriedades as $p)
                        <option value="{{ $p->id }}">{{ $p->nome }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Tipo</label>
                <select name="tipo" class="form-select">
                    <option value="condominio">Condomínio</option>
                    <option value="iptu">IPTU</option>
                    <option value="agua">Água</option>
                    <option value="energia">Energia</option>
                    <option value="outros">Outros</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Pagador</label>
                <select name="pagador" class="form-select">
                    <option value="proprietario">Proprietário</option>
                    <option value="locatario">Locatário</option>
                </select>
            </div>
            <div class="col-6">
                <label class="form-label">Valor</label>
                <input type="number" name="valor" step="0.01" class="form-control" required>
            </div>
            <div class="col-6">
                <label class="form-label">Data de Pagamento</label>
                <input type="date" name="data_pagamento" class="form-control" required>
            </div>
            <div class="col-12">
                <label class="form-label">Observação</label>
                <textarea name="observacao" class="form-control"></textarea>
            </div>
            <div class="col-12 d-flex gap-2">
                <a href="{{ route('taxas.index') }}" class="btn btn-secondary">Voltar</a>
                <button class="btn btn-success ms-auto">Salvar</button>
            </div>
        </form>
    </div>
</div>
@push('scripts-body')
    @vite(['resources/ts/taxas-assoc.ts'])
@endpush
@endsection
