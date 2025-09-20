<?php

use App\Models\Proprietario;
use function Pest\Laravel\{actingAs, get, post};

beforeEach(fn () => $this->user = Proprietario::factory()->create());

it('mostra configurações de conta', function () {
    actingAs($this->user)
        ->get(route('account.settings'))
        ->assertOk()
        ->assertViewHas(['is2FAEnabled', 'qrCodeUrl']);
});


it('ativa e desativa 2FA', function () {
    actingAs($this->user)
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