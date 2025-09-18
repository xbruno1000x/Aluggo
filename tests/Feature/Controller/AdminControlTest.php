<?php

use App\Models\Proprietario;
use Illuminate\Support\Facades\Hash;
use function Pest\Laravel\{get, post, actingAs};

beforeEach(fn () => $this->user = Proprietario::factory()->create());

it('exibe o formulário de login', function () {
    get(route('admin.login'))->assertOk()->assertViewIs('admin.login');
});

it('faz login com credenciais válidas', function () {
    $user = Proprietario::factory()->create(['password' => Hash::make('password')]);

    post(route('admin.login.post'), [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('admin.menu'));
});

it('não faz login com credenciais inválidas', function () {
    post(route('admin.login.post'), [
        'email' => 'errado@email.com',
        'password' => 'senha',
    ])->assertSessionHasErrors('email');
});

it('faz logout corretamente', function () {
    actingAs($this->user, 'proprietario');

    post(route('admin.logout'))
        ->assertRedirect(route('admin.login'));
});

it('exibe formulário de registro', function () {
    get(route('admin.register'))->assertOk()->assertViewIs('admin.register');
});

it('registra um novo proprietário', function () {
    $data = Proprietario::factory()->make()->toArray();
    $data['password'] = 'password';
    $data['password_confirmation'] = 'password';

    post(route('admin.register.post'), $data)
        ->assertRedirect(route('admin.login'))
        ->assertSessionHas('success');
});

it('exibe menu do administrador quando logado', function () {
    actingAs($this->user, 'proprietario');

    get(route('admin.menu'))->assertOk()->assertViewIs('admin.menu');
});

it('exibe formulário de 2FA', function () {
    actingAs($this->user, 'proprietario');

    get(route('admin.twofactor'))
        ->assertOk()
        ->assertViewIs('admin.twofactor');
});
