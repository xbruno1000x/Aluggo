{{-- resources/views/auth/register.blade.php --}}
@extends('layouts.guest')

@section('title', 'Cadastro - Aluggo')

@section('content')
<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="w-100" style="max-width: 400px;">

        <div class="card shadow-sm p-4">
            <header class="mb-4 text-center">
                <h4 class="card-title mb-2">Criar Conta</h4>
                <p class="text-muted mb-0">Preencha os campos abaixo para se cadastrar</p>
            </header>

            <form action="{{ route('admin.register.post') }}" method="POST" class="needs-validation" novalidate>
                @csrf

                <div class="mb-3">
                    <label for="nome" class="form-label">Nome Completo</label>
                    <input type="text" class="form-control @error('nome') is-invalid @enderror" name="nome" id="nome" placeholder="Digite seu nome completo" value="{{ old('nome') }}" required>
                    @error('nome')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="cpf" class="form-label">CPF</label>
                    <input type="text" class="form-control @error('cpf') is-invalid @enderror" name="cpf" id="cpf" placeholder="Digite seu CPF" value="{{ old('cpf') }}" required>
                    @error('cpf')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="telefone" class="form-label">Telefone</label>
                    <input type="text" class="form-control @error('telefone') is-invalid @enderror" name="telefone" id="telefone" placeholder="Digite seu telefone" value="{{ old('telefone') }}" required>
                    @error('telefone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" placeholder="Digite seu email" value="{{ old('email') }}" required>
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">Senha</label>
                    <input type="password" class="form-control @error('password') is-invalid @enderror" name="password" id="password" placeholder="Crie uma senha" required>
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="password_confirmation" class="form-label">Confirme a Senha</label>
                    <input type="password" class="form-control @error('password_confirmation') is-invalid @enderror" name="password_confirmation" id="password_confirmation" placeholder="Repita a senha" required>
                    @error('password_confirmation')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary w-100">Cadastrar</button>

                @if (session('success'))
                    <div class="alert alert-success mt-3">
                        {{ session('success') }}
                    </div>
                @endif
            </form>

            <footer class="mt-3 text-center">
                <p class="text-muted mb-0">Já tem uma conta? <a href="{{ route('admin.login') }}" class="text-decoration-none">Faça login</a></p>
            </footer>
        </div>

    </div>
</div>
@endsection