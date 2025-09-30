<?php

use App\Models\Proprietario;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use function Pest\Laravel\{actingAs, post, get};

test('mostrar login redireciona quando já autenticado', function () {
    $user = Proprietario::factory()->create();
    /** @var \App\Models\Proprietario $user */
    actingAs($user, 'proprietario');

    get(route('admin.login'))->assertRedirect(route('admin.menu'));
});

test('login redireciona para 2FA quando usuário tem two_factor_secret', function () {
    $user = Proprietario::factory()->create(['password' => Hash::make('password')]);
    $user->enableTwoFactorAuthentication();

    post(route('admin.login.post'), ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect(route('admin.twofactor'));
});

test('verifyTwoFactorRecovery seta sessão e redireciona para reset quando código válido', function () {
    $user = Proprietario::factory()->create();
    $user->enableTwoFactorAuthentication();
    $user->refresh();

    $g = new Google2FA();
    $secret = decrypt($user->two_factor_secret);
    $code = $g->getCurrentOtp($secret);

    post(route('admin.reset.twofactor.verify'), ['email' => $user->email, 'two_factor_code' => $code])
        ->assertRedirect();

    // session should have reset_email and reset_token
    $this->assertNotNull(session('reset_email'));
    $this->assertNotNull(session('reset_token'));
});

test('mostrar formulário de redefinição quando token válido na sessão', function () {
    $user = Proprietario::factory()->create();
    $token = 'tokentest';
    session(['reset_token' => $token, 'reset_email' => $user->email, 'reset_token_expires_at' => time() + 300]);

    get(route('admin.reset.password.form', ['token' => $token]))->assertOk()->assertViewIs('admin.reset-password');
});

test('resetPassword atualiza senha quando token e email válidos', function () {
    $user = Proprietario::factory()->create();
    $token = 'tokentest2';
    session(['reset_token' => $token, 'reset_email' => $user->email, 'reset_token_expires_at' => time() + 300]);

    post(route('admin.reset.password.post'), ['token' => $token, 'email' => $user->email, 'password' => 'newpassword', 'password_confirmation' => 'newpassword'])
        ->assertRedirect(route('admin.login'));

    $user->refresh();
    $this->assertTrue(Hash::check('newpassword', $user->password));
});
test('verificação 2FA durante login redireciona para menu quando código correto', function () {
    $user = Proprietario::factory()->create();
    $user->enableTwoFactorAuthentication();
    /** @var \App\Models\Proprietario $user */
    actingAs($user, 'proprietario');

    $g = new Google2FA();
    $secret = decrypt($user->two_factor_secret);
    $code = $g->getCurrentOtp($secret);

    post(route('admin.twofactor.verify'), ['two_factor_code' => $code])->assertRedirect(route('admin.menu'));
});
test('configurações de conta: atualização de senha retorna erro quando current_password inválida', function () {
    $user = Proprietario::factory()->create(['password' => Hash::make('oldpass')]);
    /** @var \App\Models\Proprietario $user */
    actingAs($user, 'proprietario');

    actingAs($user, 'proprietario')
        ->put(route('account.password.update'), ['current_password' => 'wrong', 'password' => 'newpassword', 'password_confirmation' => 'newpassword'])
        ->assertStatus(422);
});