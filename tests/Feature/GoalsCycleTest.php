<?php

use App\Livewire\Cycle;
use App\Models\Course;
use App\Models\CycleItem;
use App\Models\StudyCycle;
use App\Models\User;
use App\Services\CycleService;
use App\Services\GoalService;
use App\Services\SessionService;
use App\Services\StreakService;
use App\Services\SuggestionService;
use App\Support\Week;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Blade;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-19 10:00:00')); // quinta
    $this->user = User::factory()->create(['settings' => ['study_days' => [1, 2, 3, 4, 5], 'streak_min' => 15]]);
    $this->actingAs($this->user);
    $this->course = Course::factory()->create(['user_id' => $this->user->id]);
    $this->a = $this->course->subjects()->create(['name' => 'Álgebra']);
    $this->b = $this->course->subjects()->create(['name' => 'Biologia']);
    $this->sessions = app(SessionService::class);
});

function log_study(int $subjectId, string $date, int $minutes): void
{
    test()->sessions->record(['subject_id' => $subjectId, 'date' => $date, 'duration_seconds' => $minutes * 60, 'kind' => 'theory']);
}

it('conta a sequência sem quebrar em dias de folga', function () {
    // sex 13, (sáb/dom folga), seg 16, ter 17, qua 18 → 4 dias; hoje (qui) ainda não estudou
    foreach (['2026-11-13', '2026-11-16', '2026-11-17', '2026-11-18'] as $d) {
        log_study($this->a->id, $d, 20);
    }
    log_study($this->a->id, '2026-11-11', 20); // qua 11; qui 12 sem estudo quebra

    $s = app(StreakService::class)->compute($this->user);
    expect($s)->toBe(['current' => 4, 'best' => 4, 'today_counts' => false]);

    log_study($this->a->id, '2026-11-19', 10); // abaixo do mínimo
    expect(app(StreakService::class)->compute($this->user)['current'])->toBe(4);
    log_study($this->a->id, '2026-11-19', 10); // soma 20 min
    expect(app(StreakService::class)->compute($this->user))->toMatchArray(['current' => 5, 'today_counts' => true]);
});

it('estudar no fim de semana conta para a sequência', function () {
    foreach (['2026-11-14', '2026-11-15', '2026-11-16', '2026-11-17', '2026-11-18'] as $d) {
        log_study($this->a->id, $d, 30);
    }
    expect(app(StreakService::class)->compute($this->user)['current'])->toBe(5);
});

it('a sequência no cache é atualizada ao salvar sessão', function () {
    $svc = app(StreakService::class);
    expect($svc->for($this->user)['current'])->toBe(0);
    log_study($this->a->id, '2026-11-19', 30);
    expect($svc->for($this->user)['current'])->toBe(1);
});

it('calcula o déficit proporcional da meta semanal', function () {
    $this->a->update(['weekly_goal_min' => 7 * 60]); // 7 h/semana → 4 h até quinta (semana começa segunda)
    log_study($this->a->id, '2026-11-17', 60);

    $d = app(GoalService::class)->deficits(Week::of(now(), 1), CarbonImmutable::today());
    expect($d)->toHaveCount(1)
        ->and($d[0]['subject']->id)->toBe($this->a->id)
        ->and($d[0]['deficit'])->toBe(3 * 3600);
});

it('meta do curso sugere a disciplina menos estudada', function () {
    $this->course->update(['weekly_goal_min' => 10 * 60]);
    log_study($this->a->id, '2026-11-18', 60);

    $d = app(GoalService::class)->deficits(Week::of(now(), 1), CarbonImmutable::today());
    expect($d[0]['subject']->id)->toBe($this->b->id);
});

it('ciclo: soma no bloco certo, transborda e fecha a volta', function () {
    $cycles = app(CycleService::class);
    $cycle = StudyCycle::create(['name' => 'C']);
    $cycles->replaceItems($cycle, [
        ['subject_id' => $this->a->id, 'target_min' => 30],
        ['subject_id' => $this->b->id, 'target_min' => 30],
        ['subject_id' => $this->a->id, 'target_min' => 30],
    ]);
    $cycles->activate($cycle);

    log_study($this->a->id, '2026-11-19', 40); // 30 no 1º bloco de A, 10 no 3º
    $items = $cycle->items()->get();
    expect($items->pluck('done_seconds')->all())->toBe([1800, 0, 600])
        ->and($cycle->refresh()->currentItem()->subject_id)->toBe($this->b->id);

    log_study($this->b->id, '2026-11-19', 30);
    log_study($this->a->id, '2026-11-19', 20); // completa tudo → nova volta
    expect($cycle->refresh()->round)->toBe(2)
        ->and(CycleItem::sum('done_seconds'))->toBe(0);
});

it('ciclo inativo não recebe sessões; pular bloco', function () {
    $cycles = app(CycleService::class);
    $cycle = StudyCycle::create(['name' => 'C']);
    $cycles->replaceItems($cycle, [['subject_id' => $this->a->id, 'target_min' => 30], ['subject_id' => $this->b->id, 'target_min' => 30]]);

    log_study($this->a->id, '2026-11-19', 30);
    expect(CycleItem::sum('done_seconds'))->toBe(0);

    $cycles->activate($cycle);
    $cycles->skip($cycle->items()->first());
    expect($cycle->refresh()->currentItem()->subject_id)->toBe($this->b->id);
});

it('distribui horas por peso, em blocos intercalados', function () {
    $items = app(CycleService::class)->distribute([$this->a->id => 2, $this->b->id => 1], 180, 60);

    expect($items)->toBe([
        ['subject_id' => $this->a->id, 'target_min' => 60],
        ['subject_id' => $this->b->id, 'target_min' => 60],
        ['subject_id' => $this->a->id, 'target_min' => 60],
    ]);
});

it('monta o ciclo pelo assistente na tela', function () {
    Livewire::test(Cycle::class)
        ->call('openBuilder')
        ->set("weights.{$this->a->id}", 3)
        ->set("weights.{$this->b->id}", 0)
        ->set('totalHours', 2)
        ->call('build')
        ->assertHasNoErrors()
        ->assertSee('Álgebra');

    expect(StudyCycle::first()->is_active)->toBeTrue()
        ->and(CycleItem::count())->toBe(2)
        ->and(CycleItem::pluck('target_min')->all())->toBe([60, 60]);
});

it('sugere o que estudar: ciclo, metas e continuar', function () {
    $topic = $this->b->topics()->create(['title' => 'Células']);
    $this->sessions->record(['subject_id' => $this->b->id, 'topic_id' => $topic->id, 'date' => '2026-11-18', 'duration_seconds' => 1200, 'kind' => 'video']);

    $cycles = app(CycleService::class);
    $cycle = StudyCycle::create(['name' => 'C']);
    $cycles->replaceItems($cycle, [['subject_id' => $this->a->id, 'target_min' => 45]]);
    $cycles->activate($cycle);

    $s = app(SuggestionService::class)->forToday(Week::of(now(), 1));
    expect($s[0]['subject_id'])->toBe($this->a->id)
        ->and($s[0]['reason'])->toContain('Ciclo')
        ->and(collect($s)->firstWhere('topic_id', $topic->id)['reason'])->toBe('Continuar de onde parou');

    $this->get('/')->assertSee('O que estudar agora')->assertSee('Células');
});

it('o calendário reserva margem para o contorno do dia de hoje', function () {
    $html = Blade::render('<x-ui.heatmap :weeks="8" />');
    expect($html)->toContain('viewBox="-2 -2')->toContain('stroke="var(--pen)"');
});
