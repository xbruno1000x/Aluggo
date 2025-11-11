<?php

use App\Models\Proprietario;
use Illuminate\Support\Facades\Hash;
use PragmaRX\Google2FA\Google2FA;
use function Pest\Laravel\{get, post, actingAs, put};

beforeEach(fn () => $this->user = Proprietario::factory()->create());

test('exibe o formulário de login', function () {
    get(route('admin.login'))->assertOk()->assertViewIs('admin.login');
});

test('faz login com credenciais válidas', function () {
    $user = Proprietario::factory()->create(['password' => Hash::make('password')]);

    post(route('admin.login.post'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.menu'));
});

test('não faz login com credenciais inválidas', function () {
    post(route('admin.login.post'), [
        'email' => 'errado@email.com',
        'password' => 'senha',
    ])->assertSessionHasErrors('email');
});

test('faz logout corretamente', function () {
    actingAs($this->user, 'proprietario');

    post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));
});

test('exibe formulário de registro', function () {
    get(route('admin.register'))->assertOk()->assertViewIs('admin.register');
});

test('registra um novo proprietário', function () {
    $data = Proprietario::factory()->make()->toArray();
    $data['password'] = 'Password123!';
    $data['password_confirmation'] = 'Password123!';

    post(route('admin.register.post'), $data)
        ->assertRedirect(route('admin.menu'))
        ->assertSessionHas('success');
    
    // Verifica que o usuário foi autenticado
    expect(Auth::guard('proprietario')->check())->toBeTrue();
    expect(Auth::guard('proprietario')->user()->email)->toBe($data['email']);
});

test('exibe menu do administrador quando logado', function () {
    actingAs($this->user, 'proprietario');

    get(route('admin.menu'))->assertOk()->assertViewIs('admin.menu');
});

test('exibe formulário de 2FA', function () {
    actingAs($this->user, 'proprietario');

    get(route('admin.twofactor'))
        ->assertOk()
        ->assertViewIs('admin.twofactor');
});

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
    $secret = \Illuminate\Support\Facades\Crypt::decryptString($user->two_factor_secret);
    $code = $g->getCurrentOtp($secret);

    post(route('admin.reset.twofactor.verify'), ['email' => $user->email, 'two_factor_code' => $code])
        ->assertRedirect();

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

    post(route('admin.reset.password.post'), ['token' => $token, 'email' => $user->email, 'password' => 'NewPass123!', 'password_confirmation' => 'NewPass123!'])
        ->assertRedirect(route('admin.login'));

    $user->refresh();
    $this->assertTrue(Hash::check('NewPass123!', $user->password));
});

test('verificação 2FA durante login redireciona para menu quando código correto', function () {
    $user = Proprietario::factory()->create();
    $user->enableTwoFactorAuthentication();
    /** @var \App\Models\Proprietario $user */
    actingAs($user, 'proprietario');

    $g = new Google2FA();
    $secret = \Illuminate\Support\Facades\Crypt::decryptString($user->two_factor_secret);
    $code = $g->getCurrentOtp($secret);

    post(route('admin.twofactor.verify'), ['two_factor_code' => $code])->assertRedirect(route('admin.menu'));
});

test('configurações de conta: atualização de senha retorna erro quando current_password inválida', function () {
    $user = Proprietario::factory()->create(['password' => Hash::make('OldPass123!')]);
    /** @var \App\Models\Proprietario $user */
    actingAs($user, 'proprietario');

    actingAs($user, 'proprietario')
        ->put(route('account.password.update'), ['current_password' => 'wrong', 'password' => 'NewPass456@', 'password_confirmation' => 'NewPass456@'])
        ->assertStatus(422);
});

test('exibe formulário de recuperação de senha', function () {
    get(route('admin.reset'))->assertOk()->assertViewIs('admin.password-recovery');
});

test('sendResetLinkEmail valida email obrigatório', function () {
    post(route('admin.recovery-email'), ['email' => ''])
        ->assertSessionHasErrors('email');
});

test('sendResetLinkEmail valida email existente', function () {
    post(route('admin.recovery-email'), ['email' => 'naoexiste@example.com'])
        ->assertSessionHasErrors('email');
});

test('sendResetLinkEmail retorna erro quando email não encontrado', function () {
    $response = post(route('admin.recovery-email'), ['email' => 'inexistente@test.com']);
    $response->assertSessionHasErrors('email');
});

test('sendResetLinkEmail respeita throttle quando token recente', function () {
    $user = Proprietario::factory()->create();
    
    \Illuminate\Support\Facades\DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => \Illuminate\Support\Facades\Hash::make('sometoken'),
        'created_at' => now(),
    ]);
    
    post(route('admin.recovery-email'), ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHasErrors('email');
});

test('sendResetLinkEmail retorna erro quando falha envio de email', function () {
    \Illuminate\Support\Facades\Mail::fake();
    \Illuminate\Support\Facades\Mail::shouldReceive('send')->andThrow(new \Exception('Mail error'));
    
    $user = Proprietario::factory()->create();

    $response = post(route('admin.recovery-email'), ['email' => $user->email]);
    
    expect($response->status())->toBe(302);
});

test('showResetPasswordFormEmail redireciona sem email na query', function () {
    get(route('admin.reset.password.email.form', ['token' => 'test123']))
        ->assertRedirect(route('admin.reset'))
        ->assertSessionHas('error');
});

test('showResetPasswordFormEmail redireciona quando usuário não existe', function () {
    get(route('admin.reset.password.email.form', ['token' => 'test123', 'email' => 'naoexiste@test.com']))
        ->assertRedirect(route('admin.reset'))
        ->assertSessionHas('error');
});

test('showResetPasswordFormEmail redireciona quando token inválido', function () {
    $user = Proprietario::factory()->create();
    
    get(route('admin.reset.password.email.form', ['token' => 'invalidtoken', 'email' => $user->email]))
        ->assertRedirect(route('admin.reset'))
        ->assertSessionHas('error');
});

test('showResetPasswordFormEmail exibe view quando token válido', function () {
    $user = Proprietario::factory()->create();
    $token = \Illuminate\Support\Str::random(64);
    
    \Illuminate\Support\Facades\DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => \Illuminate\Support\Facades\Hash::make($token),
        'created_at' => now(),
    ]);
    
    get(route('admin.reset.password.email.form', ['token' => $token, 'email' => $user->email]))
        ->assertOk()
        ->assertViewIs('admin.reset-password-email')
        ->assertViewHas('token', $token)
        ->assertViewHas('email', $user->email);
});

test('resetPasswordEmail valida campos obrigatórios', function () {
    post(route('admin.reset.password.email.post'), [])
        ->assertSessionHasErrors(['token', 'email', 'password']);
});

test('resetPasswordEmail valida confirmação de senha', function () {
    $user = Proprietario::factory()->create();
    
    post(route('admin.reset.password.email.post'), [
        'token' => 'test',
        'email' => $user->email,
        'password' => 'newpass123',
        'password_confirmation' => 'different',
    ])->assertSessionHasErrors('password');
});

test('resetPasswordEmail reseta senha com token válido', function () {
    $user = Proprietario::factory()->create();
    $token = \Illuminate\Support\Str::random(64);
    
    \Illuminate\Support\Facades\DB::table('password_reset_tokens')->insert([
        'email' => $user->email,
        'token' => \Illuminate\Support\Facades\Hash::make($token),
        'created_at' => now(),
    ]);
    
    post(route('admin.reset.password.email.post'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPass123!',
        'password_confirmation' => 'NewPass123!',
    ])->assertRedirect(route('admin.login'))
      ->assertSessionHas('success');
    
    $user->refresh();
    expect(Hash::check('NewPass123!', $user->password))->toBeTrue();
});

test('resetPasswordEmail retorna erro com token inválido', function () {
    $user = Proprietario::factory()->create();
    
    post(route('admin.reset.password.email.post'), [
        'token' => 'invalid_token',
        'email' => $user->email,
        'password' => 'NewPass123!',
        'password_confirmation' => 'NewPass123!',
    ])->assertRedirect()
      ->assertSessionHasErrors(['email']);
});

test('resetPasswordEmail retorna erro com email inválido', function () {
    post(route('admin.reset.password.email.post'), [
        'token' => 'sometoken',
        'email' => 'invalid@test.com',
        'password' => 'newpassword123',
        'password_confirmation' => 'newpassword123',
    ])->assertSessionHasErrors('email');
});

test('verifyTwoFactor retorna erro com código inválido', function () {
    $user = Proprietario::factory()->create();
    $user->enableTwoFactorAuthentication();
    /** @var \App\Models\Proprietario $user */
    actingAs($user, 'proprietario');

    post(route('admin.twofactor.verify'), ['two_factor_code' => '000000'])
        ->assertRedirect()
        ->assertSessionHas('error');
});

test('showResetPasswordForm redireciona quando token expirado', function () {
    $user = Proprietario::factory()->create();
    $token = 'expiredtoken';
    session(['reset_token' => $token, 'reset_email' => $user->email, 'reset_token_expires_at' => time() - 100]);

    get(route('admin.reset.password.form', ['token' => $token]))
        ->assertRedirect(route('admin.reset'))
        ->assertSessionHas('error');
});

test('showResetPasswordForm redireciona quando token não corresponde', function () {
    $user = Proprietario::factory()->create();
    session(['reset_token' => 'token1', 'reset_email' => $user->email, 'reset_token_expires_at' => time() + 300]);

    get(route('admin.reset.password.form', ['token' => 'token2']))
        ->assertRedirect(route('admin.reset'))
        ->assertSessionHas('error');
});

test('resetPassword retorna erro quando token expirado', function () {
    $user = Proprietario::factory()->create();
    $token = 'expiredtoken';
    session(['reset_token' => $token, 'reset_email' => $user->email, 'reset_token_expires_at' => time() - 100]);

    post(route('admin.reset.password.post'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPass123!',
        'password_confirmation' => 'NewPass123!'
    ])->assertSessionHasErrors('email');
});

test('resetPassword limpa sessão após reset bem-sucedido', function () {
    $user = Proprietario::factory()->create();
    $token = 'validtoken';
    session(['reset_token' => $token, 'reset_email' => $user->email, 'reset_token_expires_at' => time() + 300]);

    post(route('admin.reset.password.post'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewPass123!',
        'password_confirmation' => 'NewPass123!'
    ])->assertRedirect(route('admin.login'));

    expect(session('reset_token'))->toBeNull();
    expect(session('reset_email'))->toBeNull();
    expect(session('reset_token_expires_at'))->toBeNull();
});

test('verifyTwoFactorRecovery retorna erro com código inválido', function () {
    $user = Proprietario::factory()->create();
    $user->enableTwoFactorAuthentication();

    post(route('admin.reset.twofactor.verify'), [
        'email' => $user->email,
        'two_factor_code' => '000000'
    ])->assertRedirect()
      ->assertSessionHas('error');
});
