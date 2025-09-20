<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\PropriedadeController;
use App\Http\Controllers\ImovelController;
use Illuminate\Support\Facades\Auth;

// Rotas de Login
Route::get('/admin/login', [AdminController::class, 'showLoginForm'])->name('admin.login');
Route::post('/admin/login', [AdminController::class, 'login'])->name('admin.login.post');
Route::post('/admin/logout', [AdminController::class, 'logout'])->name('admin.logout');

// Rota de Cadastro
Route::get('/admin/register', [AdminController::class, 'showRegisterForm'])->name('admin.register');
Route::post('/admin/register', [AdminController::class, 'register'])->name('admin.register.post');

// Rotas de Recuperação de Senha
Route::get('/admin/reset', [AdminController::class, 'showResetForm'])->name('admin.reset');
Route::post('/admin/password-recovery', [AdminController::class, 'sendResetLink'])->name('admin.password-recovery');
Route::post('/admin/password-recovery', [AdminController::class, 'sendResetLink'])->name('admin.recovery-email');

// Nova rota para verificação via 2FA (recuperação de senha)
Route::post('/admin/reset/verify-2fa', [AdminController::class, 'verifyTwoFactorRecovery'])->name('admin.reset.twofactor.verify');

// Rotas para o formulário de redefinição e processamento (token temporário)
Route::get('/admin/reset/password/{token}', [AdminController::class, 'showResetPasswordForm'])->name('admin.reset.password.form');
Route::post('/admin/reset/password', [AdminController::class, 'resetPassword'])->name('admin.reset.password.post');

// Rotas protegidas com o guard 'proprietario'
Route::middleware(['auth:proprietario'])->group(function () {
    Route::get('/admin/menu', [AdminController::class, 'menu'])->name('admin.menu');
    Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
});

// Rotas protegidas para configurações da conta com o guard 'proprietario'
Route::middleware(['auth:proprietario'])->group(function () {
    Route::get('/account/settings', [AccountSettingsController::class, 'show'])->name('account.settings');
    Route::post('/account/settings/toggle-2fa', [AccountSettingsController::class, 'toggleTwoFactorAuthentication'])->name('account.toggle2fa');
    Route::put('/account/password', [AccountSettingsController::class, 'updatePassword'])->name('account.password.update');
});

// Rotas para autenticação de 2 fatores no login
Route::middleware('auth:proprietario')->get('/admin/twofactor', [AdminController::class, 'showTwoFactorForm'])->name('admin.twofactor');
Route::middleware('auth:proprietario')->post('/admin/twofactor', [AdminController::class, 'verifyTwoFactor'])->name('admin.twofactor.verify');

// Rotas protegidas para Propriedades e Imóveis
Route::middleware(['auth:proprietario'])->group(function () {
    Route::resource('propriedades', PropriedadeController::class);

    Route::resource('imoveis', ImovelController::class)->parameters([
        'imoveis' => 'imovel'
    ]);
});

// Redirecionamento para login padrão
Route::get('/login', function () {
    return redirect()->route('admin.login');
})->name('login');

// Página inicial que redireciona com base na autenticação
Route::get('/', function () {
    if (Auth::guard('proprietario')->check()) {
        return redirect()->route('admin.menu');
    }
    return redirect()->route('admin.login');
});