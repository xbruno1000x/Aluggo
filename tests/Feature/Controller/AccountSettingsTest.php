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
    $user = Proprietario::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('OldPass123!')]);

    actingAs($user, 'proprietario')
        ->putJson(route('account.password.update'), [
            'current_password' => 'OldPass123!',
            'password' => 'NewPass456@',
            'password_confirmation' => 'NewPass456@',
        ])->assertStatus(200)->assertJson(['status' => 'Senha alterada com sucesso!']);

    $user->refresh();
    expect(\Illuminate\Support\Facades\Hash::check('NewPass456@', $user->password))->toBeTrue();
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

test('updateEmail atualiza e-mail quando senha correta', function () {
    /** @var \App\Models\Proprietario $user */
    $user = Proprietario::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('Password123!')]);
    $oldEmail = $user->email;
    $newEmail = 'newemail_' . time() . '@example.com';

    actingAs($user, 'proprietario')
        ->put(route('account.email.update'), [
            'email' => $newEmail,
            'current_password' => 'Password123!',
        ])->assertRedirect(route('account.settings'))
        ->assertSessionHas('status', 'E-mail alterado com sucesso!');

    $user->refresh();
    expect($user->email)->toBe($newEmail);
    expect($user->email)->not->toBe($oldEmail);
});

test('updateEmail retorna erro quando senha incorreta', function () {
    /** @var \App\Models\Proprietario $user */
    $user = Proprietario::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('Password123!')]);
    
    actingAs($user, 'proprietario')
        ->put(route('account.email.update'), [
            'email' => 'anotheremail_' . time() . '@example.com',
            'current_password' => 'wrongpassword',
        ])->assertSessionHasErrors(['current_password']);
});

test('updateEmail retorna erro quando e-mail já existe', function () {
    $existingEmail = 'existing_' . time() . '@example.com';
    $otherUser = Proprietario::factory()->create(['email' => $existingEmail]);
    $user = Proprietario::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('Password123!')]);
    
    actingAs($user, 'proprietario')
        ->put(route('account.email.update'), [
            'email' => $existingEmail,
            'current_password' => 'Password123!',
        ])->assertSessionHasErrors(['email']);
});

test('updatePhone atualiza telefone quando senha correta', function () {
    /** @var \App\Models\Proprietario $user */
    $user = Proprietario::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('Password123!')]);
    $oldPhone = $user->telefone;
    $newPhone = '+5511999887766';

    actingAs($user, 'proprietario')
        ->put(route('account.phone.update'), [
            'telefone' => $newPhone,
            'current_password' => 'Password123!',
        ])->assertRedirect(route('account.settings'))
        ->assertSessionHas('status', 'Telefone alterado com sucesso!');

    $user->refresh();
    // O accessor formata o telefone ao recuperar
    expect($user->telefone)->toBe('+55 (11)99988-7766');

    expect($user->telefone)->not->toBe($oldPhone);
});

test('updatePhone retorna erro quando senha incorreta', function () {
    $user = Proprietario::factory()->create(['password' => \Illuminate\Support\Facades\Hash::make('Password123!')]);
    
    actingAs($user, 'proprietario')
        ->put(route('account.phone.update'), [
            'telefone' => '+5511999887766',
            'current_password' => 'wrongpassword',
        ])->assertSessionHasErrors(['current_password']);
});
