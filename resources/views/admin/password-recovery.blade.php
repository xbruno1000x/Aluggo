@extends('layouts.guest') {{-- Layout sem navbar para pré-login --}}

@section('title', 'Recuperação de Senha')
@section('header', 'Recuperação de Senha')

@section('content')
<div class="d-flex justify-content-center">
    <div class="w-100" style="max-width: 450px;">

        <div class="card p-4 shadow-sm">
            <header class="mb-4 text-center">
                <h4 class="card-title mb-2">Recupere sua senha</h4>
                <p class="text-muted mb-0">Escolha um método para recuperação</p>
            </header>

            <!-- Botões de seleção -->
            <div class="d-flex justify-content-between mb-3">
                <button type="button" class="btn btn-outline-primary w-50 me-1 active" id="email-method">E-mail</button>
                <button type="button" class="btn btn-outline-primary w-50 ms-1" id="2fa-method">2FA</button>
            </div>

            <!-- Formulário para E-mail -->
            <form id="email-form" class="recovery-form active" action="{{ route('admin.recovery-email') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <input type="email" name="email" placeholder="Digite seu e-mail" value="{{ old('email') }}" required class="form-control @error('email') is-invalid @enderror">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @if (session('status'))
                    <div class="alert alert-success">{{ session('status') }}</div>
                @endif
                <button type="submit" class="btn btn-success w-100">Enviar link de recuperação</button>
            </form>

            <!-- Formulário para 2FA -->
            <form id="2fa-form" class="recovery-form mt-3" 
                  action="{{ route('admin.reset.twofactor.verify') }}" 
                  method="POST" style="display: none;">
                @csrf
                <div class="mb-3">
                    <input type="email" name="email" placeholder="Digite seu e-mail" value="{{ old('email') }}" required class="form-control @error('email') is-invalid @enderror">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                <div class="mb-3">
                    <input type="text" name="two_factor_code" placeholder="Código de autenticação" required class="form-control @error('two_factor_code') is-invalid @enderror">
                    @error('two_factor_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif
                <button type="submit" class="btn btn-success w-100">Verificar Código</button>
            </form>

            <footer class="mt-4 text-center">
                <p class="small mb-0">Já tem uma conta? 
                    <a href="{{ route('admin.login') }}" class="text-primary">Faça login</a>
                </p>
            </footer>
        </div>

    </div>
</div>

@push('scripts-body')
<script src="{{ asset('js/password-recovery.js') }}" defer></script>
@endpush
@endsection