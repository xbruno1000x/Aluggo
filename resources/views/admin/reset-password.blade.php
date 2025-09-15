{{-- resources/views/auth/password-reset.blade.php --}}
@extends('layouts.guest')

@section('title', 'Redefinir Senha')

@section('content')
<div class="text-center mb-4">
    <h1 class="fw-bold">Redefinir Senha</h1>
    <p class="text-muted">Informe sua nova senha</p>
</div>

<form action="{{ route('admin.reset.password.post') }}" method="POST" class="needs-validation" novalidate>
    @csrf
    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label for="email" class="form-label">E-mail</label>
        <input type="email" class="form-control @error('email') is-invalid @enderror" name="email" id="email" placeholder="Digite seu e-mail" required>
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

    <button type="submit" class="btn btn-primary w-100">Redefinir Senha</button>
</form>

<div class="text-center mt-3">
    <p class="text-muted">Lembrou sua senha? <a href="{{ route('admin.login') }}" class="text-decoration-none">Faça login</a></p>
</div>
@endsection