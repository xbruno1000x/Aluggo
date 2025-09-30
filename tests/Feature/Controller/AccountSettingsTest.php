<?php

use App\Models\Proprietario;
use function Pest\Laravel\{actingAs, get, post};

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