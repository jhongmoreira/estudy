<?php

use App\Livewire\Notifications;
use App\Models\AgendaItem;
use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use App\Notifications\StudyAlert;
use App\Services\NotificationService;
use App\Services\SessionService;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(function () {
    Notification::fake();
    $this->user = User::factory()->create(['settings' => ['study_days' => [1, 2, 3, 4, 5], 'reminder_time' => '19:00', 'daily_goal_min' => 60]]);
    Auth::setUser($this->user);
    $this->course = Course::factory()->create(['user_id' => $this->user->id]);
    $this->subject = $this->course->subjects()->create(['name' => 'Química']);
    $this->topic = $this->subject->topics()->create(['title' => 'Ligações']);
    Auth::forgetUser();
});

function runAt(string $when): int
{
    test()->travelTo(CarbonImmutable::parse($when));

    return app(NotificationService::class)->dispatchDue();
}

it('lembrete diário no horário, uma vez só, em dia de estudo', function () {
    expect(runAt('2026-11-17 18:45'))->toBe(0); // antes do horário
    expect(runAt('2026-11-17 19:05'))->toBe(1);
    expect(runAt('2026-11-17 19:20'))->toBe(0); // já enviado hoje
    expect(runAt('2026-11-17 20:30'))->toBe(0); // fora da janela

    Notification::assertSentTo($this->user, StudyAlert::class, fn (StudyAlert $n) => $n->title === 'Bora estudar?' && $n->url === '/estudar');
    expect(runAt('2026-11-21 19:05'))->toBe(0); // sábado não é dia de estudo
});

it('não lembra quem já bateu a meta; com estudo parcial diz quanto falta', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-17 10:00'));
    Auth::setUser($this->user);
    app(SessionService::class)->record(['subject_id' => $this->subject->id, 'date' => '2026-11-17', 'duration_seconds' => 1800, 'kind' => 'theory']);
    Auth::forgetUser();

    runAt('2026-11-17 19:10');
    Notification::assertSentTo($this->user, StudyAlert::class, fn (StudyAlert $n) => $n->title === 'Faltam 30 min para a meta de hoje');

    $this->travelTo(CarbonImmutable::parse('2026-11-18 19:10'));
    Auth::setUser($this->user);
    app(SessionService::class)->record(['subject_id' => $this->subject->id, 'date' => '2026-11-18', 'duration_seconds' => 3600, 'kind' => 'theory']);
    Auth::forgetUser();
    expect(app(NotificationService::class)->dispatchDue())->toBe(0);
});

it('avisa revisões de manhã e prazos na véspera e no dia', function () {
    Auth::setUser($this->user);
    Review::create(['topic_id' => $this->topic->id, 'subject_id' => $this->subject->id, 'due_on' => '2026-11-17']);
    AgendaItem::create(['course_id' => $this->course->id, 'subject_id' => $this->subject->id, 'title' => 'P1', 'due_on' => '2026-11-18', 'due_time' => '10:00']);
    Auth::forgetUser();

    expect(runAt('2026-11-17 08:10'))->toBe(1); // revisões
    Notification::assertSentTo($this->user, StudyAlert::class, fn ($n) => $n->title === '1 revisão para hoje');

    runAt('2026-11-17 19:15'); // véspera + lembrete diário
    Notification::assertSentTo($this->user, StudyAlert::class, fn ($n) => $n->title === 'Amanhã: P1');

    runAt('2026-11-18 08:05');
    Notification::assertSentTo($this->user, StudyAlert::class, fn ($n) => $n->title === 'Hoje: P1' && str_contains($n->body, 'às 10:00'));
});

it('avisa a sequência em risco às 21h', function () {
    Auth::setUser($this->user);
    foreach (['2026-11-12', '2026-11-13', '2026-11-16'] as $d) {
        app(SessionService::class)->record(['subject_id' => $this->subject->id, 'date' => $d, 'duration_seconds' => 1800, 'kind' => 'theory']);
    }
    Auth::forgetUser();
    $this->user->updateSettings(['notifications.daily' => false]);

    expect(runAt('2026-11-17 21:05'))->toBe(1);
    Notification::assertSentTo($this->user, StudyAlert::class, fn ($n) => str_contains($n->title, 'sequência de 3 dias'));
});

it('respeita as preferências desligadas', function () {
    $this->user->updateSettings(['notifications.daily' => false]);

    expect(runAt('2026-11-17 19:05'))->toBe(0);
});

it('tela de notificações salva preferências e horário, e envia teste', function () {
    $this->actingAs($this->user);

    Livewire::test(Notifications::class)
        ->set('prefs.streak', false)
        ->set('reminderTime', '25:00')->assertHasErrors('reminderTime')
        ->set('reminderTime', '07:30')->assertHasNoErrors()
        ->call('sendTest');

    $this->user->refresh();
    expect($this->user->wantsNotification('streak'))->toBeFalse()
        ->and($this->user->setting('reminder_time'))->toBe('07:30');
    Notification::assertSentTo($this->user, StudyAlert::class, fn ($n) => $n->title === 'Teste do Estudy');
});

it('assina e cancela push neste aparelho', function () {
    $this->actingAs($this->user);
    $payload = ['endpoint' => 'https://fcm.googleapis.com/fcm/send/abc', 'keys' => ['p256dh' => 'chave', 'auth' => 'segredo']];

    $this->postJson(route('push.subscribe'), $payload)->assertOk();
    expect($this->user->pushSubscriptions()->count())->toBe(1);

    $this->postJson(route('push.unsubscribe'), ['endpoint' => $payload['endpoint']])->assertOk();
    expect($this->user->pushSubscriptions()->count())->toBe(0);
});
