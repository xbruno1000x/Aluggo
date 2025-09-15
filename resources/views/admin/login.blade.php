{{-- resources/views/auth/login.blade.php --}}
@extends('layouts.guest')
@section('title', 'Login - Administrador')

@section('content')
<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="w-100" style="max-width: 400px;">

        <div class="card shadow-sm p-4">
            <header class="mb-4 text-center">
                <h4 class="card-title mb-2">Bem-vindo de volta 👋</h4>
                <p class="text-muted mb-0">Insira suas informações</p>
            </header>

            {{-- Login com Google --}}
            <div class="d-grid gap-2 mb-3">
                <button type="button" class="btn btn-outline-danger">
                    <img src="{{ asset('images/google-logo.png') }}" alt="Google Logo" style="height:20px; margin-right:8px;">
                    Login com Google
                </button>
            </div>

            <p class="text-center text-muted mb-3">ou</p>

            <form action="{{ route('admin.login.post') }}" method="POST">
                @csrf

                <div class="mb-3">
                    <input type="email" name="email" placeholder="Email" required value="{{ old('email') }}" class="form-control">
                </div>

                <div class="mb-3">
                    <input type="password" name="password" placeholder="Senha" required class="form-control">
                    @if ($errors->has('password'))
                        <div class="text-danger small mt-1">{{ $errors->first('password') }}</div>
                    @endif
                </div>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div class="form-check">
                        <input type="checkbox" name="remember" id="remember" class="form-check-input">
                        <label for="remember" class="form-check-label">Lembrar por 30 dias</label>
                    </div>
                    <a href="{{ route('admin.reset') }}" class="text-decoration-none">Esqueceu sua senha?</a>
                </div>

                @if (session('error'))
                    <div class="alert alert-danger">{{ session('error') }}</div>
                @endif

                <button type="submit" class="btn btn-primary w-100">Login</button>
            </form>

            <footer class="mt-3 text-center">
                <p class="small mb-0">
                    Não tem uma conta? 
                    <a href="{{ route('admin.register') }}" class="text-primary">Cadastrar</a>
                </p>
            </footer>
        </div>

    </div>
</div>
@endsection