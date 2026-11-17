<?php

use App\Livewire\Formation;
use App\Livewire\Stats;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\User;
use App\Services\SessionService;
use App\Services\StatsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-17 20:00:00'));
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->course = Course::factory()->create(['user_id' => $this->user->id, 'name' => 'Python', 'color' => '#059669']);
    $this->other = Course::factory()->create(['user_id' => $this->user->id, 'name' => 'Inglês', 'color' => '#e11d48']);
    $this->s1 = $this->course->subjects()->create(['name' => 'Funções']);
    $this->s2 = $this->other->subjects()->create(['name' => 'Listening']);
    $svc = app(SessionService::class);
    $svc->record(['subject_id' => $this->s1->id, 'date' => '2026-11-17', 'started_at' => '2026-11-17 08:00', 'duration_seconds' => 3600, 'kind' => 'exercises', 'questions_total' => 20, 'questions_correct' => 15]);
    $svc->record(['subject_id' => $this->s1->id, 'date' => '2026-11-16', 'started_at' => '2026-11-16 21:00', 'duration_seconds' => 1800, 'kind' => 'theory', 'pages' => 30]);
    $svc->record(['subject_id' => $this->s2->id, 'date' => '2026-11-10', 'duration_seconds' => 1200, 'kind' => 'video']);
    $svc->record(['subject_id' => $this->s2->id, 'date' => '2026-06-01', 'duration_seconds' => 999, 'kind' => 'video']); // fora de 30 dias
});

it('resume o período e filtra por curso', function () {
    $s = app(StatsService::class)->scope('30', null);
    expect($s->summary())->toMatchArray(['seconds' => 6600, 'sessions' => 3, 'days' => 3, 'avg_per_day' => 2200]);

    $s = app(StatsService::class)->scope('30', $this->course->id);
    expect($s->summary()['seconds'])->toBe(5400);

    expect(app(StatsService::class)->scope('all', null)->summary()['sessions'])->toBe(4);
});

it('monta a série por dia (até 31 dias) ou por semana', function () {
    $t = app(StatsService::class)->scope('7', null)->timeline();
    expect($t['bucket'])->toBe('dia')->and($t['bars'])->toHaveCount(7)
        ->and(end($t['bars'])['total'])->toBe(3600)
        ->and(end($t['bars'])['segments'][0]['color'])->toBe('#059669');

    $t = app(StatsService::class)->scope('90', null)->timeline();
    expect($t['bucket'])->toBe('semana')->and(collect($t['bars'])->sum('total'))->toBe(6600);
});

it('distribui por curso, tipo, período do dia, acertos e leitura', function () {
    $s = app(StatsService::class)->scope('30', null);

    expect($s->byCourse()[0])->toMatchArray(['name' => 'Python', 'value' => 5400])
        ->and($s->byKind()[0]['name'])->toBe('Exercícios')
        ->and($s->byDayPart())->toMatchArray(['Manhã' => 3600, 'Noite' => 1800, 'Tarde' => 0])
        ->and($s->questions())->toMatchArray(['total' => 20, 'correct' => 15])
        ->and($s->reading())->toBe(['pages' => 30, 'per_hour' => 60.0]);
});

it('mostra o mapa de dificuldade', function () {
    $this->s1->topics()->createMany([['title' => 'a', 'difficulty' => 3], ['title' => 'b', 'difficulty' => 1, 'status' => 'done'], ['title' => 'c']]);

    $d = app(StatsService::class)->scope('30', null)->difficulty();
    expect($d[0])->toMatchArray(['name' => 'Funções', 'hard' => 1, 'easy' => 1, 'unrated' => 1, 'done' => 1, 'total' => 3]);
});

it('renderiza a tela de estatísticas', function () {
    Livewire::test(Stats::class)
        ->assertSee('Tempo por dia')->assertSee('Python')->assertSee('75%')->assertSee('Você estuda mais de')
        ->set('period', '365')->assertSee('Tempo por semana')
        ->set('period', 'xyz')->assertSet('period', '30');
});

it('histórico de formação soma horas por tipo e ano', function () {
    $this->course->update(['status' => 'completed', 'completed_on' => '2026-10-01', 'workload_hours' => 40, 'type' => 'free']);
    $this->other->update(['status' => 'completed', 'completed_on' => '2025-12-01', 'type' => 'language']); // sem carga: usa horas registradas (0)

    Livewire::test(Formation::class)
        ->assertSee('40 h')->assertSee('Curso livre / online')->assertSee('2026: ')
        ->set('tab', 'andamento')->assertSee('Nenhum curso em andamento');
});

it('envia, baixa e remove o certificado; outro usuário não acessa', function () {
    Storage::fake('local');
    $this->course->update(['status' => 'completed', 'completed_on' => '2026-10-01']);

    Livewire::test(Formation::class)
        ->call('startUpload', $this->course->id)
        ->set('file', UploadedFile::fake()->create('virus.exe', 10, 'application/octet-stream'))
        ->call('saveCertificate')->assertHasErrors('file')
        ->set('file', UploadedFile::fake()->create('certificado.pdf', 200, 'application/pdf'))
        ->call('saveCertificate')->assertHasNoErrors();

    $cert = Certificate::first();
    Storage::disk('local')->assertExists($cert->file_path);
    $this->get(route('formation.certificate', $this->course))->assertOk()->assertDownload('certificado.pdf');

    $intruder = User::factory()->create();
    $this->actingAs($intruder)->get(route('formation.certificate', $this->course->id))->assertNotFound();

    $this->actingAs($this->user);
    Livewire::test(Formation::class)->call('removeCertificate', $this->course->id);
    Storage::disk('local')->assertMissing($cert->file_path);
});

it('exporta o CSV da formação', function () {
    $res = $this->get(route('formation.csv'));
    $res->assertOk();
    expect($res->streamedContent())->toContain('Curso;Tipo')->toContain('Python');
});
