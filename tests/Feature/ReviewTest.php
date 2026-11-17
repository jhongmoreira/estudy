<?php

use App\Livewire\Focus;
use App\Livewire\Reviews;
use App\Livewire\Settings;
use App\Livewire\SubjectShow;
use App\Models\Course;
use App\Models\Review;
use App\Models\User;
use App\Services\ReviewService;
use App\Services\SessionService;
use App\Services\TimerService;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    // Terça-feira; o usuário estuda de segunda a sexta
    $this->travelTo(CarbonImmutable::parse('2026-11-17 10:00:00'));
    $this->user = User::factory()->create(['settings' => ['study_days' => [1, 2, 3, 4, 5]]]);
    $this->actingAs($this->user);
    $course = Course::factory()->create(['user_id' => $this->user->id]);
    $this->subject = $course->subjects()->create(['name' => 'Direito Civil']);
    $this->topic = $this->subject->topics()->create(['title' => 'Contratos']);
    $this->sessions = app(SessionService::class);
    $this->reviews = app(ReviewService::class);
});

function study(array $extra = []): void
{
    test()->sessions->record([
        'subject_id' => test()->subject->id, 'topic_id' => test()->topic->id,
        'date' => CarbonImmutable::today()->toDateString(), 'duration_seconds' => 1800, 'kind' => 'theory', ...$extra,
    ]);
}

it('agenda a 1ª revisão só na primeira sessão do tópico', function () {
    study();
    study(); // estudar de novo não duplica

    $pending = Review::pending()->get();
    expect($pending)->toHaveCount(1)
        ->and($pending[0]->step)->toBe(1)
        ->and($pending[0]->due_on->toDateString())->toBe('2026-11-18');
});

it('não agenda revisão para sessão sem tópico ou com agendamento desligado', function () {
    $this->sessions->record(['subject_id' => $this->subject->id, 'date' => '2026-11-17', 'duration_seconds' => 900, 'kind' => 'theory']);
    $this->user->updateSettings(['reviews_auto' => false]);
    study();

    expect(Review::count())->toBe(0);
});

it('empurra a revisão para o próximo dia de estudo', function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-20 10:00:00')); // sexta
    study();

    expect(Review::first()->due_on->toDateString())->toBe('2026-11-23'); // segunda, pula o fim de semana
});

it('avança, repete ou recomeça conforme a avaliação', function () {
    study();
    $r1 = Review::first();

    $this->travelTo(CarbonImmutable::parse('2026-11-18 09:00'));
    $r2 = $this->reviews->complete($r1, 'good');
    expect($r1->refresh()->status)->toBe('done')
        ->and($r2->step)->toBe(2)
        ->and($r2->due_on->toDateString())->toBe('2026-11-25'); // +7

    $r2b = $this->reviews->complete($r2, 'hard');
    expect($r2b->step)->toBe(2)->and($r2b->due_on->toDateString())->toBe('2026-11-25');

    $again = $this->reviews->complete($r2b, 'forgot');
    expect($again->step)->toBe(1)->and($again->due_on->toDateString())->toBe('2026-11-19');
});

it('encerra a cadeia depois do último passo', function () {
    study();
    $r = Review::first();
    $r = $this->reviews->complete($r, 'good');
    $r = $this->reviews->complete($r, 'good');
    expect($r->step)->toBe(3);

    expect($this->reviews->complete($r, 'good'))->toBeNull()
        ->and(Review::pending()->count())->toBe(0);
});

it('uma sessão de revisão conclui a revisão pendente com a avaliação', function () {
    study();
    $this->travelTo(CarbonImmutable::parse('2026-11-18 09:00'));

    app(TimerService::class)->start($this->subject, $this->topic, 'review');
    $this->travel(15)->minutes();
    Livewire::test(Focus::class)->call('finish')->set('reviewRating', 'hard')->call('save')->assertHasNoErrors();

    $done = Review::where('status', 'done')->first();
    expect($done->rating)->toBe('hard')
        ->and($done->session_id)->not->toBeNull()
        ->and(Review::pending()->first()->step)->toBe(1); // hard no passo 1 repete o passo 1
});

it('reagenda atrasadas respeitando o limite por dia', function () {
    $this->user->updateSettings(['reviews_per_day' => 2]);
    foreach (range(1, 5) as $i) {
        $t = $this->subject->topics()->create(['title' => "T$i"]);
        Review::create(['topic_id' => $t->id, 'subject_id' => $this->subject->id, 'due_on' => '2026-11-1'.$i]);
    }

    expect($this->reviews->rescheduleOverdue())->toBe(5);
    expect(Review::pending()->orderBy('id')->pluck('due_on')->map->toDateString()->all())
        ->toBe(['2026-11-17', '2026-11-17', '2026-11-18', '2026-11-18', '2026-11-19']);
});

it('tela Revisões: marcar como feita, pular e mudar data', function () {
    study();
    $r = Review::first();
    $r->update(['due_on' => '2026-11-16']); // atrasada

    Livewire::test(Reviews::class)
        ->assertSee('Atrasadas')->assertSee('Contratos')
        ->call('skip', $r->id);
    expect($r->refresh()->due_on->toDateString())->toBe('2026-11-18');

    Livewire::test(Reviews::class)
        ->call('openMove', $r->id)->set('moveDate', '2026-11-10')->call('move')->assertHasErrors('moveDate')
        ->set('moveDate', '2026-11-17')->call('move')->assertHasNoErrors()
        ->call('rate', $r->id)->call('complete', 'good');

    expect($r->refresh()->status)->toBe('done')->and(Review::pending()->first()->step)->toBe(2);
});

it('mostra revisões na tela Hoje e o contador no menu', function () {
    study();
    Review::first()->update(['due_on' => '2026-11-17']);

    $this->get('/')->assertSee('Revisões de hoje')->assertSee('Contratos');
});

it('agenda manualmente pelo tópico', function () {
    Livewire::test(SubjectShow::class, ['subject' => $this->subject])
        ->call('editTopic', $this->topic->id)
        ->call('reviewTopicOn', 'tomorrow');

    expect(Review::pending()->first()->due_on->toDateString())->toBe('2026-11-18');
});

it('valida e salva os intervalos de revisão', function () {
    Livewire::test(Settings::class)
        ->set('review_intervals', '30, 7')->call('save')->assertHasErrors('review_intervals')
        ->call('usePreset', '1, 3, 7, 15, 30')->call('save')->assertHasNoErrors();

    expect($this->user->refresh()->reviewIntervals())->toBe([1, 3, 7, 15, 30]);
});
