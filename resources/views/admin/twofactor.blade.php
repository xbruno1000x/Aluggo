{{-- resources/views/auth/twofactor.blade.php --}}
@extends('layouts.guest')

@section('title', 'Autenticação de Dois Fatores')

@section('content')
<div class="d-flex justify-content-center align-items-center vh-100 bg-dark">
    <div class="w-100" style="max-width: 400px;">

        <div class="card shadow-sm border-0 bg-info p-4">
            <header class="mb-4 text-center">
                <h1 class="fw-bold text-primary">Autenticação de Dois Fatores</h1>
                <p class="text-dark small mb-0">Digite o código enviado para o seu dispositivo</p>
            </header>

            <form action="{{ route('admin.twofactor.verify') }}" method="POST" class="needs-validation" novalidate data-spinner>
                @csrf

                <div class="mb-3">
                    <label for="two_factor_code" class="form-label text-dark">Código de Autenticação</label>
                    <input type="text" class="form-control @if(session('error')) is-invalid @endif" name="two_factor_code" id="two_factor_code" placeholder="Digite o código" required>
                    @if(session('error'))
                        <div class="invalid-feedback">{{ session('error') }}</div>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <span class="btn-text">Verificar</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </form>

            <div class="text-center mt-3">
                <p class="small mb-0 text-dark">
                    Problemas para receber o código? 
                    <form action="{{ route('admin.logout') }}" method="POST" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-link text-warning p-0 m-0 align-baseline">Voltar ao login</button>
                    </form>
                </p>
            </div>
        </div>

    </div>
</div>

@push('scripts-body')
    @vite(['resources/ts/form-spinner.ts'])
@endpush
@endsection