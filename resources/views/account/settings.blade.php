{{-- resources/views/account/settings.blade.php --}}
@extends('layouts.app')

@section('title', 'Configurações da Conta')
@section('header', 'Configurações da Conta')

@section('content')
<div class="d-flex flex-column align-items-center">
    <div class="w-100" style="max-width: 500px;">

        <p class="mb-4">Gerencie as configurações da sua conta.</p>

        <!-- Mensagem de sucesso -->
        @if(session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        <!-- Formulário de ativação/desativação 2FA -->
        <form method="POST" action="{{ route('account.toggle2fa') }}" class="mb-4">
            @csrf
            <button type="submit" class="btn btn-warning w-100">
                @if($is2FAEnabled)
                    Desativar Autenticação de 2 Fatores
                @else
                    Ativar Autenticação de 2 Fatores
                @endif
            </button>
        </form>

        <!-- QR Code -->
        @if($is2FAEnabled && $qrCodeUrl)
            <div class="text-center">
                <p>Escaneie o QR Code abaixo com o Google Authenticator:</p>
                <div class="d-inline-block p-3 bg-light rounded">
                    {!! $qrCodeUrl !!}
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
