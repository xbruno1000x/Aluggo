<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AccountSettingsController;
use App\Http\Controllers\PropriedadeController;
use App\Http\Controllers\ImovelController;
use App\Http\Controllers\LocatarioController;
use App\Http\Controllers\AluguelController;
use App\Http\Controllers\ObraController;
use App\Http\Controllers\TransacaoController;
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
Route::post('/admin/recovery/email', [AdminController::class, 'sendResetLinkEmail'])->name('admin.recovery-email');

// Nova rota para verificação via 2FA (recuperação de senha)
Route::post('/admin/reset/verify-2fa', [AdminController::class, 'verifyTwoFactorRecovery'])->name('admin.reset.twofactor.verify');

// Rotas para o formulário de redefinição e processamento (token temporário via 2FA)
Route::get('/admin/reset/password/{token}', [AdminController::class, 'showResetPasswordForm'])->name('admin.reset.password.form');
Route::post('/admin/reset/password', [AdminController::class, 'resetPassword'])->name('admin.reset.password.post');

// Rotas dedicadas à recuperação por e-mail
Route::get('/admin/reset/email/{token}', [AdminController::class, 'showResetPasswordFormEmail'])->name('admin.reset.password.email.form');
Route::post('/admin/reset/email', [AdminController::class, 'resetPasswordEmail'])->name('admin.reset.password.email.post');

// Rotas protegidas com o guard 'proprietario' — unificadas
Route::middleware(['auth:proprietario'])->group(function () {
    // Admin
    Route::get('/admin/menu', [AdminController::class, 'menu'])->name('admin.menu');
    Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');

    // Account settings
    Route::get('/account/settings', [AccountSettingsController::class, 'show'])->name('account.settings');
    Route::post('/account/settings/toggle-2fa', [AccountSettingsController::class, 'toggleTwoFactorAuthentication'])->name('account.toggle2fa');
    Route::put('/account/password', [AccountSettingsController::class, 'updatePassword'])->name('account.password.update');

    // Two-factor auth (login)
    Route::get('/admin/twofactor', [AdminController::class, 'showTwoFactorForm'])->name('admin.twofactor');
    Route::post('/admin/twofactor', [AdminController::class, 'verifyTwoFactor'])->name('admin.twofactor.verify');

    // Recursos da aplicação
    Route::resource('propriedades', PropriedadeController::class);

    Route::resource('imoveis', ImovelController::class)->parameters([
        'imoveis' => 'imovel'
    ]);

    Route::resource('alugueis', AluguelController::class)->parameters([
        'alugueis' => 'aluguel'
    ]);

    Route::resource('obras', ObraController::class)->parameters([
        'obras' => 'obra'
    ]);

    Route::resource('locatarios', LocatarioController::class)->parameters([
        'locatarios' => 'locatario'
    ]);

    Route::resource('transacoes', TransacaoController::class)->parameters([
        'transacoes' => 'transacao'
    ]);

    // Relatórios financeiros
    Route::get('relatorios', [\App\Http\Controllers\RelatorioController::class, 'index'])->name('relatorios.index');

    // Pagamentos - MVP manual confirmation
    Route::get('pagamentos', [\App\Http\Controllers\PagamentoController::class, 'index'])->name('pagamentos.index');
    Route::post('pagamentos/{pagamento}/mark-paid', [\App\Http\Controllers\PagamentoController::class, 'markPaid'])->name('pagamentos.markPaid');
    Route::post('pagamentos/{pagamento}/revert', [\App\Http\Controllers\PagamentoController::class, 'revert'])->name('pagamentos.revert');
    Route::post('pagamentos/mark-all', [\App\Http\Controllers\PagamentoController::class, 'markAllPaid'])->name('pagamentos.markAll');
    Route::post('alugueis/{aluguel}/renew', [\App\Http\Controllers\PagamentoController::class, 'renew'])->name('alugueis.renew');
    Route::patch('alugueis/{aluguel}/adjust', [AluguelController::class, 'adjust'])->name('alugueis.adjust');
    Route::post('alugueis/{aluguel}/terminate', [AluguelController::class, 'terminate'])->name('alugueis.terminate');

    // Taxas (condominio, iptu, outros)
    Route::resource('taxas', \App\Http\Controllers\TaxaController::class)->parameters(['taxas' => 'taxa']);
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
    return view('welcome');
});