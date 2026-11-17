<?php

use App\Livewire\More;
use App\Livewire\Profile;
use App\Livewire\Settings;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

it('salva as configurações de estudo', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Settings::class)
        ->set('daily_goal_min', 90)
        ->set('streak_min', 20)
        ->call('toggleDay', 0)
        ->set('week_starts_on', 0)
        ->call('save')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->dailyGoalMinutes())->toBe(90)
        ->and($user->setting('streak_min'))->toBe(20)
        ->and($user->studyDays())->toBe([0, 1, 2, 3, 4, 5, 6])
        ->and($user->weekStartsOn())->toBe(0);
});

it('valida a meta e o mínimo da sequência', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Settings::class)
        ->set('daily_goal_min', 2)
        ->set('streak_min', 500)
        ->call('save')
        ->assertHasErrors(['daily_goal_min', 'streak_min']);
});

it('troca o tema', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(More::class)->call('setTheme', 'dark')->assertDispatched('theme-changed');
    expect($user->refresh()->theme())->toBe('dark');

    Livewire::test(More::class)->call('setTheme', 'neon');
    expect($user->refresh()->theme())->toBe('dark');
});

it('atualiza o perfil e a senha', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)
        ->set('name', 'Novo Nome')
        ->call('saveProfile')
        ->set('current_password', 'password')
        ->set('password', 'novasenha123')
        ->set('password_confirmation', 'novasenha123')
        ->call('updatePassword')
        ->assertHasNoErrors();

    $user->refresh();
    expect($user->name)->toBe('Novo Nome')
        ->and(Hash::check('novasenha123', $user->password))->toBeTrue();
});

it('exclui a conta com a senha correta', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test(Profile::class)->set('delete_password', 'errada')->call('deleteAccount')->assertHasErrors('delete_password');
    Livewire::test(Profile::class)->set('delete_password', 'password')->call('deleteAccount')->assertRedirect(route('login'));

    expect(User::find($user->id))->toBeNull();
});
