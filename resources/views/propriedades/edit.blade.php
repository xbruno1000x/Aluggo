{{-- resources/views/propriedades/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar Propriedade')
@section('header', 'Editar Propriedade')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 500px;">

        <!-- Título centralizado -->
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>

        <!-- Formulário centralizado -->
        <form action="{{ route('propriedades.update', $propriedade) }}" method="POST" class="row g-3" data-spinner>
            @csrf
            @method('PUT')

            <div class="col-12">
                <label for="nome" class="form-label">Nome:</label>
                <input type="text" id="nome" name="nome" value="{{ $propriedade->nome }}" required class="form-control">
            </div>

            <div class="col-12">
                <label for="endereco" class="form-label">Endereço:</label>
                <input type="text" id="endereco" name="endereco" value="{{ $propriedade->endereco }}" required class="form-control">
            </div>

            <div class="col-6">
                <label for="cep" class="form-label">CEP:</label>
                <input type="text" id="cep" name="cep" value="{{ $propriedade->cep }}" class="form-control">
            </div>

            <div class="col-6">
                <label for="cidade" class="form-label">Cidade:</label>
                <input type="text" id="cidade" name="cidade" value="{{ $propriedade->cidade }}" class="form-control">
            </div>

            <div class="col-6">
                <label for="estado" class="form-label">Estado:</label>
                <input type="text" id="estado" name="estado" value="{{ $propriedade->estado }}" class="form-control">
            </div>

            <div class="col-12">
                <label for="bairro" class="form-label">Bairro:</label>
                <input type="text" id="bairro" name="bairro" value="{{ $propriedade->bairro }}" required class="form-control">
            </div>

            <div class="col-12">
                <label for="descricao" class="form-label">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="4" class="form-control">{{ $propriedade->descricao }}</textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">
                    <span class="btn-text">Salvar Alterações</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </div>
        </form>

        <!-- Botão Voltar -->
        <div class="mt-3 text-center">
            <a href="{{ route('propriedades.index') }}" class="btn btn-secondary">Voltar para Lista</a>
        </div>

    </div>
</div>

@push('scripts-body')
    @vite(['resources/ts/form-spinner.ts'])
@endpush
@endsection