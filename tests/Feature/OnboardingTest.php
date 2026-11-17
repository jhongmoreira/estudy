<?php

use App\Livewire\Onboarding;
use App\Models\User;
use Livewire\Livewire;

it('salva meta e dias de estudo e libera o app', function () {
    $user = User::factory()->newcomer()->create();
    $this->actingAs($user);

    Livewire::test(Onboarding::class)
        ->call('setGoal', 120)
        ->call('next')
        ->assertSet('step', 2)
        ->call('toggleDay', 6) // tira o sábado
        ->call('finish')
        ->assertRedirect(route('home'));

    $user->refresh();
    expect($user->onboarded_at)->not->toBeNull()
        ->and($user->dailyGoalMinutes())->toBe(120)
        ->and($user->studyDays())->toBe([1, 2, 3, 4, 5]);

    $this->get('/')->assertOk();
});

it('exige pelo menos um dia de estudo', function () {
    $this->actingAs(User::factory()->newcomer()->create(['settings' => ['study_days' => [1]]]));

    Livewire::test(Onboarding::class)
        ->set('study_days', [])
        ->call('finish')
        ->assertHasErrors('study_days');
});

it('pode ser pulado com os valores padrão', function () {
    $user = User::factory()->newcomer()->create();
    $this->actingAs($user);

    Livewire::test(Onboarding::class)->call('skip')->assertRedirect(route('home'));

    expect($user->refresh()->onboarded_at)->not->toBeNull()
        ->and($user->dailyGoalMinutes())->toBe(60);
});

it('não volta ao onboarding depois de concluído', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(Onboarding::class)->assertRedirect(route('home'));
});
