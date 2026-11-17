<?php

use App\Events\SessionRecorded;
use App\Livewire\Focus;
use App\Livewire\SessionForm;
use App\Livewire\Sessions;
use App\Livewire\Settings;
use App\Livewire\Study;
use App\Models\ActiveTimer;
use App\Models\Course;
use App\Models\StudySession;
use App\Models\User;
use App\Services\SessionService;
use App\Services\TimerService;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-17 10:00:00'));
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->course = Course::factory()->create(['user_id' => $this->user->id]);
    $this->subject = $this->course->subjects()->create(['name' => 'Cálculo']);
    $this->topic = $this->subject->topics()->create(['title' => 'Limites']);
});

it('conta o tempo líquido descontando pausas', function () {
    $timers = app(TimerService::class);
    $timer = $timers->start($this->subject, $this->topic, 'theory');
    expect($this->topic->refresh()->status)->toBe('doing');

    $this->travel(10)->minutes();
    $timers->pause($timer);
    $this->travel(5)->minutes();
    expect($timer->refresh()->elapsedSeconds())->toBe(600); // pausado: não avança

    $timers->resume($timer);
    $this->travel(20)->minutes();
    expect($timer->refresh()->elapsedSeconds())->toBe(1800)
        ->and($timer->paused_seconds)->toBe(300);
});

it('permite só um cronômetro por usuário', function () {
    $timers = app(TimerService::class);
    $timers->start($this->subject, null, 'theory');
    $timers->start($this->subject, null, 'theory');
})->throws(ValidationException::class);

it('cronômetros de usuários diferentes não se misturam', function () {
    app(TimerService::class)->start($this->subject, null, 'theory');

    $other = User::factory()->create();
    $this->actingAs($other);
    expect(ActiveTimer::count())->toBe(0);
});

it('avisa quando o cronômetro ficou ligado mais de 4 horas', function () {
    $timers = app(TimerService::class);
    $timer = $timers->start($this->subject, null, 'theory');
    $this->travel(5)->hours();

    expect($timers->freeze($timer))->toBe(['seconds' => 5 * 3600, 'forgotten' => true]);
});

it('do play ao "Como foi?": salva a sessão e conclui o tópico', function () {
    Event::fake([SessionRecorded::class]);

    Livewire::test(Study::class)
        ->call('chooseSubject', $this->subject->id)
        ->call('chooseTopic', $this->topic->id)
        ->set('kind', 'exercises')
        ->call('start')
        ->assertRedirect(route('focus'));

    $this->travel(42)->minutes();

    Livewire::test(Focus::class)
        ->call('finish')
        ->assertSet('duration', '42 min')
        ->assertSet('kind', 'exercises')
        ->set('questionsTotal', 20)
        ->set('questionsCorrect', 17)
        ->set('markDone', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('home'));

    $session = StudySession::first();
    expect($session->duration_seconds)->toBe(42 * 60)
        ->and($session->source)->toBe('timer')
        ->and($session->date->toDateString())->toBe('2026-11-17')
        ->and($session->course_id)->toBe($this->course->id)
        ->and($session->questions_correct)->toBe(17)
        ->and($this->topic->refresh()->status)->toBe('done')
        ->and(ActiveTimer::count())->toBe(0);
    Event::assertDispatched(SessionRecorded::class);
});

it('sessão que cruza a meia-noite fica no dia em que começou', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-17 23:30:00'));
    app(TimerService::class)->start($this->subject, null, 'theory');
    $this->travel(60)->minutes();

    Livewire::test(Focus::class)->call('finish')->call('save')->assertHasNoErrors();

    expect(StudySession::first()->date->toDateString())->toBe('2026-11-17');
});

it('valida a duração e os acertos no "Como foi?"', function () {
    app(TimerService::class)->start($this->subject, null, 'theory');
    $this->travel(30)->minutes();

    Livewire::test(Focus::class)
        ->call('finish')
        ->set('duration', 'abc')->call('save')->assertHasErrors('duration')
        ->set('duration', '30 min')->set('questionsTotal', 5)->set('questionsCorrect', 9)->call('save')->assertHasErrors('questionsCorrect');

    expect(StudySession::count())->toBe(0)->and(ActiveTimer::count())->toBe(1);
});

it('descarta o cronômetro sem salvar', function () {
    app(TimerService::class)->start($this->subject, null, 'theory');

    Livewire::test(Focus::class)->call('discard')->assertRedirect(route('home'));

    expect(ActiveTimer::count())->toBe(0)->and(StudySession::count())->toBe(0);
});

it('com cronômetro ligado, Estudar leva ao modo foco e a barra aparece nas telas', function () {
    app(TimerService::class)->start($this->subject, null, 'theory');

    Livewire::test(Study::class)->assertRedirect(route('focus'));
    $this->get('/')->assertSee('Cálculo')->assertSee(route('focus'));
});

it('registra estudo manual, no passado mas nunca no futuro', function () {
    Livewire::test(SessionForm::class)
        ->call('open', $this->subject->id)
        ->set('date', '2026-11-20')->set('duration', '1h')
        ->call('save')->assertHasErrors('date')
        ->set('date', '2026-11-15')->set('time', '19:30')->set('duration', '1h30')->set('pages', 12)
        ->call('save')->assertHasNoErrors()->assertDispatched('session-saved');

    $s = StudySession::first();
    expect($s->duration_seconds)->toBe(5400)
        ->and($s->source)->toBe('manual')
        ->and($s->started_at->format('Y-m-d H:i'))->toBe('2026-11-15 19:30')
        ->and($s->pages)->toBe(12);
});

it('edita e exclui sessões', function () {
    $s = app(SessionService::class)->record(['subject_id' => $this->subject->id, 'date' => '2026-11-17', 'duration_seconds' => 1800, 'kind' => 'theory']);

    Livewire::test(SessionForm::class)
        ->call('open', null, $s->id)
        ->assertSet('duration', '30 min')
        ->set('duration', '50')
        ->call('save')->assertHasNoErrors();
    expect($s->refresh()->duration_seconds)->toBe(3000);

    Livewire::test(SessionForm::class)->call('open', null, $s->id)->call('delete');
    expect(StudySession::count())->toBe(0);
});

it('recusa tópico de outra disciplina', function () {
    $other = $this->course->subjects()->create(['name' => 'Outra']);
    $foreign = $other->topics()->create(['title' => 'X']);

    app(SessionService::class)->record(['subject_id' => $this->subject->id, 'topic_id' => $foreign->id, 'date' => '2026-11-17', 'duration_seconds' => 600, 'kind' => 'theory']);
})->throws(ModelNotFoundException::class);

it('lista o histórico por dia com totais e filtros', function () {
    $svc = app(SessionService::class);
    $svc->record(['subject_id' => $this->subject->id, 'date' => '2026-11-17', 'duration_seconds' => 1800, 'kind' => 'theory']);
    $svc->record(['subject_id' => $this->subject->id, 'date' => '2026-11-17', 'duration_seconds' => 1200, 'kind' => 'exercises']);
    $svc->record(['subject_id' => $this->subject->id, 'date' => '2026-09-01', 'duration_seconds' => 600, 'kind' => 'theory']);

    Livewire::test(Sessions::class)
        ->assertSee('Hoje')->assertSee('50 min')->assertSee('2 sessões')
        ->set('period', 'all')->assertSee('3 sessões')
        ->set('kind', 'exercises')->assertSee('1 sessão');
});

it('mostra o tempo de hoje e da semana na tela Hoje', function () {
    app(SessionService::class)->record(['subject_id' => $this->subject->id, 'date' => '2026-11-17', 'duration_seconds' => 1500, 'kind' => 'theory']);

    $this->get('/')->assertSee('Faltam 35 min')->assertSee('25m');
});

it('salva as configurações do Pomodoro', function () {
    Livewire::test(Settings::class)
        ->set('pomodoro.focus', 50)->set('pomodoro.short', 10)
        ->call('save')->assertHasNoErrors();

    expect($this->user->refresh()->pomodoro())->toBe(['focus' => 50, 'short' => 10, 'long' => 15, 'every' => 4]);
});
