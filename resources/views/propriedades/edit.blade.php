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
        <form action="{{ route('propriedades.update', $propriedade) }}" method="POST" class="row g-3">
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

            <div class="col-12">
                <label for="descricao" class="form-label">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="4" required class="form-control">{{ $propriedade->descricao }}</textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">Salvar Alterações</button>
            </div>
        </form>

        <!-- Botão Voltar -->
        <div class="mt-3 text-center">
            <a href="{{ route('propriedades.index') }}" class="btn btn-secondary">Voltar para Lista</a>
        </div>

    </div>
</div>
@endsection