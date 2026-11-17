<?php

use App\Livewire\Reports;
use App\Models\AgendaItem;
use App\Models\Course;
use App\Models\User;
use App\Services\ReportService;
use App\Services\SessionService;
use Carbon\CarbonImmutable;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-17 20:00'));
    $this->user = User::factory()->create(['name' => 'Bia Teste']);
    $this->actingAs($this->user);
    $this->course = Course::factory()->create(['user_id' => $this->user->id, 'name' => 'Direito', 'type' => 'undergrad', 'status' => 'completed', 'completed_on' => '2026-07-01', 'workload_hours' => 3600]);
    $this->subject = $this->course->subjects()->create(['name' => 'Constitucional', 'term' => '2026.1', 'passing_grade' => 6]);
    $topic = $this->subject->topics()->create(['title' => 'Direitos fundamentais', 'difficulty' => 3]);
    app(SessionService::class)->record(['subject_id' => $this->subject->id, 'topic_id' => $topic->id, 'date' => '2026-11-10', 'started_at' => '2026-11-10 19:00', 'duration_seconds' => 5400, 'kind' => 'exercises', 'questions_total' => 10, 'questions_correct' => 8]);
    AgendaItem::create(['course_id' => $this->course->id, 'subject_id' => $this->subject->id, 'title' => 'P1', 'due_on' => '2026-11-01', 'grade' => 7, 'max_grade' => 10]);
    AgendaItem::create(['course_id' => $this->course->id, 'subject_id' => $this->subject->id, 'title' => 'P2', 'due_on' => '2026-12-01']);
});

function pdfOk($response): void
{
    $response->assertOk()->assertHeader('content-type', 'application/pdf');
    expect(substr($response->getContent(), 0, 5))->toBe('%PDF-');
}

it('gera o relatório de estudos com e sem a lista de sessões', function () {
    $data = app(ReportService::class)->study($this->user, CarbonImmutable::parse('2026-11-01'), CarbonImmutable::parse('2026-11-17'), null, true);
    expect($data['summary']['seconds'])->toBe(5400)->and($data['sessions'])->toHaveCount(1);

    $html = view('reports.study', $data)->render();
    expect($html)->toContain('Relatório de estudos')->toContain('Constitucional')->toContain('80%')->toContain('1h30');

    pdfOk($this->get(route('reports.study', ['de' => '2026-11-01', 'ate' => '2026-11-17', 'sessoes' => 1])));
    pdfOk($this->get(route('reports.study'))); // padrão: últimos 30 dias
    $this->get(route('reports.study', ['de' => '2026-11-17', 'ate' => '2026-11-01']))->assertSessionHasErrors('ate');
});

it('gera o boletim com média e situação', function () {
    $html = view('reports.grades', app(ReportService::class)->grades($this->user, $this->course))->render();
    expect($html)->toContain('Boletim de notas')->toContain('Constitucional')->toContain('Em dia')->toContain('pendente');

    pdfOk($this->get(route('reports.grades', $this->course)));
});

it('gera o progresso do curso', function () {
    $html = view('reports.course', app(ReportService::class)->course($this->user, $this->course))->render();
    expect($html)->toContain('Direitos fundamentais')->toContain('●●●')->toContain('P2');

    pdfOk($this->get(route('reports.course', $this->course)));
});

it('gera o histórico de formação', function () {
    $html = view('reports.formation', app(ReportService::class)->formation($this->user, false))->render();
    expect($html)->toContain('Histórico de formação')->toContain('3600 h')->toContain('Bia Teste');

    pdfOk($this->get(route('reports.formation', ['andamento' => 1])));
});

it('não gera relatório de curso de outra pessoa', function () {
    $this->actingAs(User::factory()->create());
    $this->get(route('reports.course', $this->course->id))->assertNotFound();
    $this->get(route('reports.grades', $this->course->id))->assertNotFound();
});

it('tela de relatórios monta os links e os atalhos de período', function () {
    Livewire::test(Reports::class)
        ->assertSet('gradesCourse', $this->course->id)
        ->call('preset', 'last_month')
        ->assertSet('from', '2026-10-01')->assertSet('to', '2026-10-31')
        ->set('withSessions', true)
        ->assertSee('sessoes=1');
});
