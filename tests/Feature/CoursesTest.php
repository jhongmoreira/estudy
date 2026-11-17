<?php

use App\Livewire\CourseForm;
use App\Livewire\Courses;
use App\Livewire\CourseShow;
use App\Livewire\Onboarding;
use App\Livewire\PasteList;
use App\Livewire\Search;
use App\Livewire\SubjectForm;
use App\Livewire\SubjectShow;
use App\Models\Course;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Services\ProgressService;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

function makeCourse(array $attrs = []): Course
{
    return Course::factory()->create(['user_id' => auth()->id(), ...$attrs]);
}

it('cria um curso e leva para a página dele', function () {
    Livewire::test(CourseForm::class)
        ->call('open')
        ->set('name', 'Pós em Ciência de Dados')
        ->set('type', 'postgrad')
        ->set('institution', 'PUC')
        ->set('workload_hours', 360)
        ->set('weekly_goal_hours', 6)
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('courses.show', Course::first()));

    $course = Course::first();
    expect($course->user_id)->toBe($this->user->id)
        ->and($course->unitLabel())->toBe('Disciplina')
        ->and($course->weekly_goal_min)->toBe(360)
        ->and($course->color)->toBe('#3047c9');
});

it('sugere a próxima cor livre e exige data ao concluir', function () {
    makeCourse(['color' => '#3047c9']);

    Livewire::test(CourseForm::class)
        ->call('open')
        ->assertSet('color', '#7c3aed')
        ->set('name', 'X')
        ->set('status', 'completed')
        ->assertSet('completed_on', now()->toDateString())
        ->set('completed_on', null)
        ->call('save')
        ->assertHasErrors('completed_on');
});

it('edita um curso e recusa cor fora da paleta', function () {
    $course = makeCourse();

    Livewire::test(CourseForm::class)
        ->call('open', $course->id)
        ->assertSet('name', $course->name)
        ->set('color', '#000000')
        ->call('save')
        ->assertHasErrors('color')
        ->set('color', '#059669')
        ->set('status', 'paused')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('course-saved');

    expect($course->refresh()->status)->toBe('paused');
});

it('lista cursos por aba e reordena', function () {
    $a = makeCourse(['name' => 'Alfa', 'position' => 0]);
    $b = makeCourse(['name' => 'Beta', 'position' => 1]);
    makeCourse(['name' => 'Gama', 'status' => 'completed']);

    Livewire::test(Courses::class)
        ->assertSee('Alfa')->assertSee('Beta')->assertDontSee('Gama')
        ->call('setTab', 'done')->assertSee('Gama')->assertDontSee('Alfa')
        ->call('setTab', 'active')
        ->call('move', $b->id, -1);

    expect(Course::where('status', 'active')->ordered()->pluck('name')->all())->toBe(['Beta', 'Alfa']);
});

it('cria e edita disciplinas', function () {
    $course = makeCourse(['type' => 'undergrad']);

    Livewire::test(SubjectForm::class)
        ->call('open', $course->id)
        ->assertSet('unit', 'Disciplina')
        ->set('name', 'Cálculo II')
        ->set('term', '2026.2')
        ->set('passing_grade', '7')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('subject-saved');

    $subject = Subject::first();
    expect($subject->course_id)->toBe($course->id)->and($subject->term)->toBe('2026.2');

    Livewire::test(SubjectForm::class)
        ->call('open', $course->id)
        ->assertSet('term', '2026.2'); // sugere o último período
});

it('cola uma lista no curso', function () {
    $course = makeCourse();

    Livewire::test(PasteList::class)
        ->call('open', $course->id)
        ->set('text', "MÓDULO 1:\n- Aula A (30min)\n- Aula B\nMÓDULO 2:\n- Aula C")
        ->assertSee('3 tópicos')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('toast', message: '2 módulos e 3 tópicos adicionados.');

    expect($course->subjects()->count())->toBe(2)
        ->and(Topic::where('title', 'Aula A')->value('estimated_min'))->toBe(30);
});

it('gerencia tópicos na disciplina', function () {
    $course = makeCourse();
    $subject = $course->subjects()->create(['name' => 'Módulo 1']);

    $page = Livewire::test(SubjectShow::class, ['subject' => $subject])
        ->set('newTopic', 'Introdução')->call('addTopic')
        ->set('newTopic', 'Revisão')->call('addTopic')
        ->assertSee('Introdução');

    $intro = Topic::where('title', 'Introdução')->first();
    $page->call('toggle', $intro->id);
    expect($intro->refresh()->status)->toBe('done')->and($intro->done_on?->isToday())->toBeTrue();

    $page->call('setDifficulty', $intro->id, 3);
    expect($intro->refresh()->difficulty)->toBe(3);

    $page->call('editTopic', $intro->id)
        ->set('topicTitle', 'Introdução geral')
        ->set('topicStatus', 'doing')
        ->set('topicEstimated', 40)
        ->call('saveTopic')
        ->assertHasNoErrors();
    expect($intro->refresh()->title)->toBe('Introdução geral')->and($intro->done_on)->toBeNull();

    $page->set('filter', 'hard')->assertSee('Introdução geral')->assertDontSee('Revisão');

    $revisao = Topic::where('title', 'Revisão')->first();
    $page->set('filter', 'all')->call('move', $revisao->id, -1);
    expect($subject->topics()->pluck('title')->all())->toBe(['Revisão', 'Introdução geral']);
});

it('calcula o progresso por quantidade ou por minutos estimados', function () {
    $course = makeCourse();
    $byCount = $course->subjects()->create(['name' => 'A']);
    $byMinutes = $course->subjects()->create(['name' => 'B']);
    $empty = $course->subjects()->create(['name' => 'C']);

    $byCount->topics()->createMany([['title' => '1', 'status' => 'done'], ['title' => '2'], ['title' => '3', 'estimated_min' => 10], ['title' => '4']]);
    $byMinutes->topics()->createMany([['title' => '1', 'estimated_min' => 90, 'status' => 'done'], ['title' => '2', 'estimated_min' => 10]]);

    $stats = app(ProgressService::class)->forSubjects([$byCount->id, $byMinutes->id, $empty->id]);
    expect($stats[$byCount->id]['progress'])->toBe(0.25)
        ->and($stats[$byMinutes->id]['progress'])->toBe(0.9)
        ->and($stats[$empty->id]['progress'])->toBe(0.0);

    // curso: (0.25*4 + 0.9*2) / 6
    expect(app(ProgressService::class)->forCourse($course))
        ->toMatchArray(['subjects' => 3, 'total' => 6, 'done' => 2, 'progress' => round(2.8 / 6, 4)]);
});

it('exclui curso só com o nome digitado', function () {
    $course = makeCourse(['name' => 'Inglês']);
    $course->subjects()->create(['name' => 'M1'])->topics()->create(['title' => 'T']);

    Livewire::test(CourseShow::class, ['course' => $course])
        ->set('confirmName', 'outro')->call('deleteCourse')->assertHasErrors('confirmName')
        ->set('confirmName', ' inglês ')->call('deleteCourse')->assertRedirect(route('courses'));

    expect(Course::count())->toBe(0)->and(Topic::count())->toBe(0);
});

it('isola os dados entre usuários', function () {
    $mine = makeCourse(['name' => 'Meu curso']);
    $other = Course::factory()->create(['name' => 'Curso alheio']);
    $otherSubject = Subject::withoutGlobalScopes()->forceCreate(['user_id' => $other->user_id, 'course_id' => $other->id, 'name' => 'S']);

    $this->get(route('courses.show', $other))->assertNotFound();
    $this->get(route('subjects.show', $otherSubject->id))->assertNotFound();
    $this->get(route('courses.show', $mine))->assertOk()->assertSee('Meu curso');

    Livewire::test(Search::class)->set('q', 'Curso')->assertSee('Meu curso')->assertDontSee('Curso alheio');
});

it('busca cursos, disciplinas e tópicos', function () {
    $course = makeCourse(['name' => 'Física']);
    $course->subjects()->create(['name' => 'Mecânica'])->topics()->create(['title' => 'Leis de Newton']);

    Livewire::test(Search::class)
        ->set('q', 'n')->assertSee('Digite pelo menos 2 letras')
        ->set('q', 'newton')->assertSee('Leis de Newton')->assertSee('Física · Mecânica');
});

it('mostra cursos em andamento na tela Hoje', function () {
    makeCourse(['name' => 'Curso Ativo']);
    makeCourse(['name' => 'Curso Pausado', 'status' => 'paused']);

    $this->get('/')->assertSee('Curso Ativo')->assertDontSee('Curso Pausado');
});

it('cria os cursos informados no onboarding', function () {
    $user = User::factory()->newcomer()->create();
    $this->actingAs($user);

    Livewire::test(Onboarding::class)
        ->set('courses.0.name', 'Engenharia')
        ->call('addCourse')
        ->set('courses.1.type', 'language')
        ->set('courses.1.name', 'Espanhol')
        ->call('addCourse') // linha vazia é ignorada
        ->call('next')->assertSet('step', 2)
        ->call('next')->assertSet('step', 3)
        ->call('finish');

    expect(Course::orderBy('position')->get(['name', 'type'])->toArray())
        ->toBe([['name' => 'Engenharia', 'type' => 'undergrad'], ['name' => 'Espanhol', 'type' => 'language']]);
});
