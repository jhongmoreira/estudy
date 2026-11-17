<?php

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;

it('cadastra e leva ao onboarding', function () {
    $this->post('/register', [
        'name' => 'Maria Lima',
        'email' => 'maria@exemplo.com',
        'password' => 'segredo123',
        'password_confirmation' => 'segredo123',
    ])->assertRedirect('/');

    $this->assertAuthenticated();
    $this->get('/')->assertRedirect(route('onboarding'));
});

it('faz login e logout', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])->assertRedirect('/');
    $this->assertAuthenticatedAs($user);

    $this->post('/logout')->assertRedirect('/');
    $this->assertGuest();
});

it('recusa senha errada', function () {
    $user = User::factory()->create();

    $this->post('/login', ['email' => $user->email, 'password' => 'errada'])->assertSessionHasErrors('email');
    $this->assertGuest();
});

it('envia link de redefinição de senha', function () {
    Notification::fake();
    $user = User::factory()->create();

    $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
    Notification::assertSentTo($user, ResetPassword::class);
});

it('mostra as telas de login e cadastro', function () {
    $this->get('/login')->assertOk()->assertSee('Bem-vindo de volta');
    $this->get('/register')->assertOk()->assertSee('Crie sua conta');
});
