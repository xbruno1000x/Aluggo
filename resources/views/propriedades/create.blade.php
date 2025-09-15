@extends('layouts.app')

@section('title', 'Cadastrar Propriedade')
@section('header', 'Cadastrar Propriedade')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 500px;">
        <!-- Título centralizado -->
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>

        <!-- Formulário centralizado -->
        <form action="{{ route('propriedades.store') }}" method="POST" class="row g-3">
            @csrf
            <div class="col-12">
                <label for="nome" class="form-label">Nome:</label>
                <input type="text" id="nome" name="nome" required class="form-control">
            </div>

            <div class="col-12">
                <label for="endereco" class="form-label">Endereço:</label>
                <input type="text" id="endereco" name="endereco" required class="form-control">
            </div>

            <div class="col-12">
                <label for="descricao" class="form-label">Descrição:</label>
                <textarea id="descricao" name="descricao" rows="4" class="form-control"></textarea>
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">Cadastrar</button>
            </div>
        </form>
    </div>
</div>
@endsection