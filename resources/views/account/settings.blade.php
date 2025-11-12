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

        <!-- Botão para abrir modal de alteração de e-mail -->
        <button type="button" class="btn btn-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#emailModal">
            Alterar E-mail
        </button>

        <!-- Botão para abrir modal de alteração de telefone -->
        <button type="button" class="btn btn-success w-100 mb-3" data-bs-toggle="modal" data-bs-target="#phoneModal">
            Alterar Telefone
        </button>

        <!-- Botão para abrir modal de alteração de senha -->
        <button type="button" class="btn btn-success w-100 mb-4" data-bs-toggle="modal" data-bs-target="#passwordModal">
            Alterar Senha
        </button>

        <!-- Formulário de ativação/desativação 2FA -->
        <form method="POST" action="{{ route('account.toggle2fa') }}" class="mb-4" data-spinner>
            @csrf
            <button type="submit" class="btn btn-warning w-100">
                <span class="btn-text">
                    @if($is2FAEnabled)
                        Desativar Autenticação de 2 Fatores
                    @else
                        Ativar Autenticação de 2 Fatores
                    @endif
                </span>
                <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
            </button>
        </form>

        <!-- QR Code -->
        @if($is2FAEnabled && $qrCodeUrl)
            <div class="text-center">
                <p>Escaneie o QR Code abaixo com o Google Authenticator:</p>
                <div class="d-inline-block p-3 bg-light rounded">
                    {!! $qrCodeUrl !!}
                </div>
                <div class="mt-3">
                    <p class="mb-1">Se você está usando o mesmo celular e não consegue escanear o QR, copie o segredo manualmente:</p>
                    <div class="input-group mb-2">
                        <input type="text" id="tf-secret" readonly class="form-control" value="{{ $twoFactorSecretPlain ?? '' }}">
                        <button class="btn btn-outline-danger" id="btn-copy-secret" type="button">Copiar</button>
                    </div>
                    <small class="text-muted">Caso prefira, adicione manualmente o segredo ao seu app autenticador (ex.: Google Authenticator).</small>
                </div>
            </div>
        @endif

    </div>
</div>

<!-- Modal de Alteração de E-mail -->
<div class="modal fade" id="emailModal" tabindex="-1" aria-labelledby="emailModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('account.email.update') }}" data-spinner>
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="emailModalLabel">Alterar E-mail</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">

                    @if($errors->has('email') || $errors->has('current_password'))
                        <div class="alert alert-danger">
                            @if($errors->has('email'))
                                {{ $errors->first('email') }}
                            @endif
                            @if($errors->has('current_password'))
                                {{ $errors->first('current_password') }}
                            @endif
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="current_email" class="form-label">E-mail Atual</label>
                        <input type="email" id="current_email" readonly class="form-control-plaintext" value="{{ Auth::user()->email }}">
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Novo E-mail</label>
                        <input type="email" name="email" id="email" required class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="current_password_email" class="form-label">Senha Atual</label>
                        <input type="password" name="current_password" id="current_password_email" required class="form-control @error('current_password') is-invalid @enderror">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary">
                        <span class="btn-text">Alterar E-mail</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Alteração de Telefone -->
<div class="modal fade" id="phoneModal" tabindex="-1" aria-labelledby="phoneModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('account.phone.update') }}" data-spinner>
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="phoneModalLabel">Alterar Telefone</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">

                    @if($errors->has('telefone') || $errors->has('current_password'))
                        <div class="alert alert-danger">
                            @if($errors->has('telefone'))
                                {{ $errors->first('telefone') }}
                            @endif
                            @if($errors->has('current_password'))
                                {{ $errors->first('current_password') }}
                            @endif
                        </div>
                    @endif

                    <div class="mb-3">
                        <label for="current_phone" class="form-label">Telefone Atual</label>
                        <input type="text" id="current_phone" readonly class="form-control-plaintext" value="{{ Auth::user()->telefone }}">
                    </div>

                    <div class="mb-3">
                        <label for="telefone" class="form-label">Novo Telefone</label>
                        <input type="text" name="telefone" id="telefone" required class="form-control @error('telefone') is-invalid @enderror" maxlength="15" value="{{ old('telefone') }}">
                        @error('telefone')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="current_password_phone" class="form-label">Senha Atual</label>
                        <input type="password" name="current_password" id="current_password_phone" required class="form-control @error('current_password') is-invalid @enderror">
                        @error('current_password')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary">
                        <span class="btn-text">Alterar Telefone</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
            </form>
        </div>
    </div>
</div>

<!-- Modal de Alteração de Senha -->
<div class="modal fade" id="passwordModal" tabindex="-1" aria-labelledby="passwordModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="password-form" method="POST" action="{{ route('account.password.update') }}" data-spinner data-spinner-button="btn-submit-password">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="passwordModalLabel">Alterar Senha</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
                </div>
                <div class="modal-body">

                    <div id="password-form-alert" class="alert d-none" role="alert"></div>

                    <div class="mb-3">
                        <label for="current_password" class="form-label">Senha Atual</label>
                        <input type="password" name="current_password" id="current_password" required class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Nova Senha</label>
                        <input type="password" name="password" id="password" required class="form-control">
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label">Confirmar Nova Senha</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required class="form-control">
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-secondary" id="btn-submit-password">
                        <span class="btn-text">Alterar Senha</span>
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@vite(['resources/ts/account-password-modal.ts', 'resources/ts/account-2fa.ts', 'resources/ts/form-spinner.ts', 'resources/ts/password-strength.ts'])
@endsection