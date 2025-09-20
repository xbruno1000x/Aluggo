{{-- resources/views/locatarios/edit.blade.php --}}
@extends('layouts.app')

@section('title', 'Editar Locatário')
@section('header', 'Editar Locatário')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 500px;">

        <!-- Título centralizado -->
        <h2 class="mb-4 text-warning text-center">@yield('header')</h2>

        <!-- Formulário centralizado -->
        <form action="{{ route('locatarios.update', $locatario) }}" method="POST" class="row g-3">
            @csrf
            @method('PUT')

            <div class="col-12">
                <label for="nome" class="form-label">Nome:</label>
                <input 
                    type="text" 
                    id="nome" 
                    name="nome" 
                    value="{{ old('nome', $locatario->nome) }}" 
                    required 
                    class="form-control @error('nome') is-invalid @enderror"
                >
                @error('nome')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="telefone" class="form-label">Telefone:</label>
                <input 
                    type="text" 
                    id="telefone" 
                    name="telefone" 
                    value="{{ old('telefone', $locatario->telefone) }}" 
                    class="form-control @error('telefone') is-invalid @enderror"
                >
                @error('telefone')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <label for="email" class="form-label">Email:</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    value="{{ old('email', $locatario->email) }}" 
                    class="form-control @error('email') is-invalid @enderror"
                >
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>

            <div class="col-12">
                <button type="submit" class="btn btn-success w-100">Salvar Alterações</button>
            </div>
        </form>

        <!-- Botão Voltar -->
        <div class="mt-3 text-center">
            <a href="{{ route('locatarios.index') }}" class="btn btn-secondary">Voltar para Lista</a>
        </div>

    </div>
</div>
@endsection