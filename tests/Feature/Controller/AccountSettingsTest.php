<?php

use App\Models\Proprietario;
use function Pest\Laravel\{actingAs};

beforeEach(fn () => $this->user = Proprietario::factory()->create());

test('mostra configurações de conta', function () {
    actingAs($this->user)
        ->get(route('account.settings'))
        ->assertOk()
        ->assertViewHas(['is2FAEnabled', 'qrCodeUrl']);
});


test('ativa e desativa 2FA', function () {
    actingAs($this->user, 'proprietario')
        ->post(route('account.toggle2fa'))
        ->assertRedirect(route('account.settings'));

    $this->user->refresh();
    expect($this->user->two_factor_secret)->not()->toBeNull();

    actingAs($this->user)
        ->post(route('account.toggle2fa'))
        ->assertRedirect(route('account.settings'));

    $this->user->refresh();
    expect($this->user->two_factor_secret)->toBeNull();
});

test('updatePassword retorna 422 quando current_password incorreto', function () {
    $user = $this->user;

    actingAs($user, 'proprietario')
        ->putJson(route('account.password.update'), [
            'current_password' => 'wrong',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertStatus(422);
});

test('updatePassword atualiza senha quando current_password correto', function () {
    /** @var \App\Models\Proprietario $user */
    $user = Proprietario::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('oldpass')]);

    actingAs($user, 'proprietario')
        ->putJson(route('account.password.update'), [
            'current_password' => 'oldpass',
            'password' => 'newpassword',
            'password_confirmation' => 'newpassword',
        ])->assertStatus(200)->assertJson(['status' => 'Senha alterada com sucesso!']);

    $user->refresh();
    expect(\Illuminate\Support\Facades\Hash::check('newpassword', $user->password))->toBeTrue();
});

test('show exibe QR code SVG quando 2FA está ativo', function () {
    $this->user->enableTwoFactorAuthentication();
    
    actingAs($this->user, 'proprietario')
        ->get(route('account.settings'))
        ->assertOk()
        ->assertSee('QR Code', false);
});

test('show exibe secret descriptografado quando 2FA está ativo', function () {
    $this->user->enableTwoFactorAuthentication();
    $this->user->refresh();
    
    $response = actingAs($this->user, 'proprietario')
        ->get(route('account.settings'))
        ->assertOk();
    
    $secret = \Illuminate\Support\Facades\Crypt::decryptString($this->user->two_factor_secret);
    expect($response->viewData('twoFactorSecretPlain'))->toBe($secret);
});

test('show lida com secret serializado (formato antigo)', function () {
    $secret = 'TESTSECRET123456';
    $serialized = 's:16:"' . $secret . '";';
    $this->user->two_factor_secret = \Illuminate\Support\Facades\Crypt::encryptString($serialized);
    $this->user->save();
    
    $response = actingAs($this->user, 'proprietario')
        ->get(route('account.settings'))
        ->assertOk();
    
    expect($response->viewData('twoFactorSecretPlain'))->toBe($secret);
});

test('show retorna null quando decryptString e decrypt falham', function () {
    $this->user->two_factor_secret = 'invalid_encrypted_string';
    $this->user->save();
    
    $response = actingAs($this->user, 'proprietario')
        ->get(route('account.settings'))
        ->assertOk();
    
    expect($response->viewData('twoFactorSecretPlain'))->toBeNull();
});