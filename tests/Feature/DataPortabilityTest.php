<?php

use App\Livewire\Profile;
use App\Models\AgendaItem;
use App\Models\Course;
use App\Models\Review;
use App\Models\StudyCycle;
use App\Models\StudySession;
use App\Models\Topic;
use App\Models\User;
use App\Services\CycleService;
use App\Services\DataPortability;
use App\Services\SessionService;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create(['settings' => ['daily_goal_min' => 90]]);
    $this->actingAs($this->user);
    $course = Course::factory()->create(['user_id' => $this->user->id, 'name' => 'Física']);
    $subject = $course->subjects()->create(['name' => 'Óptica']);
    $topic = $subject->topics()->create(['title' => 'Refração']);
    app(SessionService::class)->record(['subject_id' => $subject->id, 'topic_id' => $topic->id, 'date' => today()->toDateString(), 'duration_seconds' => 1800, 'kind' => 'theory']);
    AgendaItem::create(['course_id' => $course->id, 'subject_id' => $subject->id, 'title' => 'P1', 'due_on' => today()->addWeek()->toDateString()]);
    $cycle = StudyCycle::create(['name' => 'C']);
    app(CycleService::class)->replaceItems($cycle, [['subject_id' => $subject->id, 'target_min' => 30]]);
});

it('exporta e importa tudo em outra conta, remapeando os ids', function () {
    $data = app(DataPortability::class)->export($this->user);
    expect($data['format'])->toBe(1)->and($data['courses'])->toHaveCount(1)->and($data['reviews'])->toHaveCount(1);

    $other = User::factory()->create();
    $this->actingAs($other);
    $counts = app(DataPortability::class)->import($other, json_decode(json_encode($data), true));

    expect($counts)->toMatchArray(['cursos' => 1, 'disciplinas' => 1, 'tópicos' => 1, 'sessões' => 1])
        ->and(Course::first()->name)->toBe('Física')
        ->and(StudySession::first()->topic->title)->toBe('Refração')
        ->and(Review::first()->topic_id)->toBe(Topic::first()->id)
        ->and(AgendaItem::count())->toBe(1)
        ->and(StudyCycle::first()->items()->count())->toBe(1)
        ->and($other->refresh()->dailyGoalMinutes())->toBe(90);

    // os dados do dono original continuam intactos
    $this->actingAs($this->user);
    expect(Course::count())->toBe(1)->and(StudySession::count())->toBe(1);
});

it('recusa arquivo que não é backup do Estudy', function () {
    app(DataPortability::class)->summarize(['app' => 'Outro']);
})->throws(ValidationException::class);

it('baixa o backup e o CSV de sessões', function () {
    $json = $this->get(route('export.backup'))->assertOk()->streamedContent();
    expect(json_decode($json, true)['courses'][0]['name'])->toBe('Física');

    $csv = $this->get(route('export.sessions'))->assertOk()->streamedContent();
    expect($csv)->toContain('Data;Início;Minutos')->toContain('Óptica');
});

it('importa pelo perfil com prévia e senha', function () {
    $file = UploadedFile::fake()->createWithContent('backup.json', json_encode(app(DataPortability::class)->export($this->user)));

    Livewire::test(Profile::class)
        ->set('backup', $file)
        ->assertSet('backupSummary.cursos', 1)
        ->set('import_password', 'errada')->call('importBackup')->assertHasErrors('import_password')
        ->set('import_password', 'password')->call('importBackup')->assertRedirect(route('home'));

    expect(Course::count())->toBe(1)->and(StudySession::count())->toBe(1);
});

it('recomeça do zero mantendo a conta e as preferências', function () {
    Livewire::test(Profile::class)
        ->set('reset_password', 'password')->call('resetData')->assertRedirect(route('home'));

    expect(Course::count())->toBe(0)->and(StudySession::count())->toBe(0)->and(Review::count())->toBe(0)
        ->and(StudyCycle::count())->toBe(0)
        ->and($this->user->refresh()->dailyGoalMinutes())->toBe(90);
});

it('o seeder de demonstração roda', function () {
    $this->seed();

    $demo = User::where('email', 'demo@estudy.test')->first();
    $this->actingAs($demo);
    expect(Course::count())->toBe(5)->and(StudySession::count())->toBeGreaterThan(100);
});
