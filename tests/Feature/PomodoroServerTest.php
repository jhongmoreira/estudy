<?php

use App\Livewire\Focus;
use App\Models\ActiveTimer;
use App\Models\Course;
use App\Models\User;
use App\Notifications\StudyAlert;
use App\Services\TimerService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->travelTo(CarbonImmutable::parse('2026-11-17 10:00:00'));
    $this->user = User::factory()->create(['settings' => ['pomodoro' => ['focus' => 25, 'short' => 5, 'long' => 15, 'every' => 4]]]);
    $this->actingAs($this->user);
    $course = Course::factory()->create(['user_id' => $this->user->id]);
    $this->subject = $course->subjects()->create(['name' => 'História']);
    $this->timers = app(TimerService::class);
});

it('com o app fechado, o cron pausa no fim exato do foco e avisa', function () {
    $timer = $this->timers->start($this->subject, null, 'theory', pomodoro: true);
    $this->travel(26)->minutes(); // cron roda 1 minuto depois do fim do foco

    expect($this->timers->pomodoroTickAll())->toBe(1);
    $timer->refresh();
    expect($timer->isPaused())->toBeTrue()
        ->and($timer->elapsedSeconds())->toBe(25 * 60) // a pausa começou às 10:25, não às 10:26
        ->and($timer->pomodoro_block)->toBe(1);
    Notification::assertSentTo($this->user, StudyAlert::class, fn ($n) => str_starts_with($n->title, 'Hora da pausa') && $n->tag === 'estudy-timer');

    // não repete
    $this->travel(1)->minutes();
    expect($this->timers->pomodoroTickAll())->toBe(0);
});

it('avisa o fim da pausa uma vez, e a pausa longa vem a cada 4 focos', function () {
    $timer = $this->timers->start($this->subject, null, 'theory', pomodoro: true);
    $this->travel(25)->minutes();
    $this->timers->pomodoroTickAll();

    $this->travel(4)->minutes();
    expect($this->timers->pomodoroTickAll())->toBe(0); // pausa curta (5 min) ainda não acabou
    $this->travel(1)->minutes();
    expect($this->timers->pomodoroTickAll())->toBe(1);
    Notification::assertSentTo($this->user, StudyAlert::class, fn ($n) => str_starts_with($n->title, 'Pausa encerrada'));
    $this->travel(1)->minutes();
    expect($this->timers->pomodoroTickAll())->toBe(0);

    // 4º foco → pausa longa de 15 min
    $timer->refresh()->update(['pomodoro_block' => 3, 'paused_at' => null, 'paused_seconds' => 0, 'started_at' => now()->subMinutes(100)]);
    expect($this->timers->pomodoroTickAll())->toBe(1);
    Notification::assertSentTo($this->user, StudyAlert::class, fn ($n) => str_contains($n->body, 'Pausa de 15 min'));
});

it('se o navegador já pausou ou já avisou, o servidor não repete', function () {
    $timer = $this->timers->start($this->subject, null, 'theory', pomodoro: true);
    $this->travel(25)->minutes();

    Livewire::test(Focus::class)->call('pause'); // o navegador pausou no fim do foco
    expect($timer->refresh()->pomodoro_block)->toBe(1);
    expect($this->timers->pomodoroTickAll())->toBe(0);

    $this->travel(6)->minutes();
    Livewire::test(Focus::class)->call('breakAlerted');
    expect($this->timers->pomodoroTickAll())->toBe(0);
    Notification::assertNothingSent();
});

it('cronômetro sem Pomodoro não é mexido pelo cron', function () {
    $timer = $this->timers->start($this->subject, null, 'theory');
    $this->travel(3)->hours();

    expect($this->timers->pomodoroTickAll())->toBe(0)->and($timer->refresh()->isPaused())->toBeFalse();
});

it('o cron trata cada usuário isoladamente', function () {
    $this->timers->start($this->subject, null, 'theory', pomodoro: true);
    Auth::forgetUser();
    $this->travel(30)->minutes();

    app(TimerService::class)->pomodoroTickAll();
    expect(ActiveTimer::withoutGlobalScopes()->first()->isPaused())->toBeTrue();
});
