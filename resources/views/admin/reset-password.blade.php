{{-- resources/views/auth/password-reset.blade.php --}}
@extends('layouts.guest')

@section('title', 'Redefinir Senha')

@section('content')
<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="w-100" style="max-width: 400px;">

        <div class="card shadow-sm p-4">
            <header class="mb-4 text-center">
                <h4 class="card-title mb-2">Redefinir Senha</h4>
                <p class="text-muted mb-0">Informe sua nova senha</p>
            </header>

            <form action="{{ route('admin.reset.password.post') }}" method="POST" class="needs-validation" novalidate data-spinner>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <label for="email" class="form-label">E-mail</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" placeholder="Digite seu e-mail" required value="{{ old('email', $email ?? '') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Nova Senha</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="Digite a nova senha" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirme a Senha</label>
                    <input type="password" class="form-control" name="password_confirmation" id="password_confirmation" placeholder="Confirme a nova senha" required>
                </div>

                <button type="submit" class="btn btn-primary w-100">
                    <span class="btn-text">Redefinir Senha</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </form>

            <footer class="mt-3 text-center">
                <p class="text-muted mb-0">Lembrou sua senha? <a href="{{ route('admin.login') }}" class="text-decoration-none">Faça login</a></p>
            </footer>
        </div>

    </div>
</div>

@push('scripts-body')
    @vite(['resources/ts/form-spinner.ts', 'resources/ts/password-strength.ts'])
@endpush
@endsection