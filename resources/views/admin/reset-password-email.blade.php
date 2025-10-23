@extends('layouts.guest')

@section('title', 'Redefinir Senha')

@section('content')
<div class="d-flex justify-content-center align-items-center min-vh-100">
    <div class="w-100" style="max-width: 450px;">
        <div class="card p-4 shadow-sm">
            <header class="mb-4 text-center">
                <h4 class="card-title mb-2">Redefinir Senha</h4>
                <p class="text-muted mb-0">Digite sua nova senha</p>
            </header>

            <form action="{{ route('admin.reset.password.email.post') }}" method="POST" data-spinner>
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <input type="hidden" name="email" value="{{ $email }}">

                <div class="mb-3">
                    <label for="email_display" class="form-label">E-mail</label>
                    <input
                        type="email"
                        class="form-control"
                        id="email_display"
                        value="{{ $email }}"
                        disabled
                    >
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Nova Senha</label>
                    <input
                        type="password"
                        name="password"
                        id="password"
                        class="form-control @error('password') is-invalid @enderror"
                        required
                        minlength="8"
                        placeholder="Mínimo 8 caracteres"
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirmar Nova Senha</label>
                    <input
                        type="password"
                        name="password_confirmation"
                        id="password_confirmation"
                        class="form-control"
                        required
                        placeholder="Digite novamente a senha"
                    >
                </div>

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <button type="submit" class="btn btn-success w-100">
                    <span class="btn-text">Redefinir Senha</span>
                    <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                </button>
            </form>

            <footer class="mt-4 text-center">
                <p class="small mb-0">
                    Lembrou sua senha?
                    <a href="{{ route('admin.login') }}" class="text-primary">Faça login</a>
                </p>
            </footer>
        </div>
    </div>
</div>

@push('scripts-body')
    @vite(['resources/ts/form-spinner.ts'])
@endpush
@endsection
