<?php

use App\Livewire\Agenda;
use App\Livewire\AgendaForm;
use App\Livewire\SubjectShow;
use App\Models\AgendaItem;
use App\Models\Course;
use App\Models\User;
use App\Services\GradeService;
use App\Services\SuggestionService;
use App\Support\Week;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Livewire\Livewire;

beforeEach(function () {
    $this->travelTo(CarbonImmutable::parse('2026-11-17 10:00:00'));
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
    $this->course = Course::factory()->create(['user_id' => $this->user->id, 'type' => 'undergrad']);
    $this->subject = $this->course->subjects()->create(['name' => 'Estatística', 'passing_grade' => 7]);
});

function item(array $attrs): AgendaItem
{
    return AgendaItem::create(['course_id' => test()->course->id, 'subject_id' => test()->subject->id, 'title' => 'X', 'due_on' => '2026-11-20', ...$attrs]);
}

it('cria um item com nota e valida a nota máxima', function () {
    Livewire::test(AgendaForm::class)
        ->call('open', null, null, $this->subject->id)
        ->assertSet('courseId', $this->course->id)
        ->set('title', 'P1')->set('dueOn', '2026-11-10')
        ->set('grade', '12')->set('maxGrade', '10')
        ->call('save')->assertHasErrors('grade')
        ->set('grade', '8.5')
        ->call('save')->assertHasNoErrors()->assertDispatched('agenda-saved');

    $item = AgendaItem::first();
    expect($item->status)->toBe('done') // com nota = feito
        ->and($item->normalizedGrade())->toBe(8.5);
});

it('calcula a média ponderada e quanto falta', function () {
    item(['title' => 'P1', 'grade' => 6, 'max_grade' => 10, 'weight' => 1]);
    item(['title' => 'Trabalho', 'grade' => 18, 'max_grade' => 20, 'weight' => 1]); // 9,0
    item(['title' => 'P2', 'weight' => 2]); // pendente

    $s = app(GradeService::class)->summary($this->subject);
    // média atual 7,5; para fechar 7 com peso total 4: (28 - 15) / 2 = 6,5
    expect($s)->toMatchArray(['average' => 7.5, 'needed' => 6.5, 'status' => 'on_track']);
});

it('identifica aprovado, em risco e impossível', function () {
    item(['grade' => 10, 'max_grade' => 10, 'weight' => 3]);
    item(['weight' => 1]);
    expect(app(GradeService::class)->summary($this->subject)['status'])->toBe('approved');

    AgendaItem::query()->delete();
    item(['grade' => 1, 'max_grade' => 10, 'weight' => 3]);
    item(['weight' => 1]);
    expect(app(GradeService::class)->summary($this->subject)['status'])->toBe('impossible');

    AgendaItem::query()->delete();
    item(['grade' => 5, 'max_grade' => 10]);
    expect(app(GradeService::class)->summary($this->subject)['status'])->toBe('at_risk');
});

it('sem média mínima mostra só a média', function () {
    $this->subject->update(['passing_grade' => null]);
    item(['grade' => 8, 'max_grade' => 10]);

    expect(app(GradeService::class)->summary($this->subject))->toMatchArray(['average' => 8.0, 'status' => 'no_target']);
});

it('agenda separa atrasados, próximos e feitos; alterna feito', function () {
    $late = item(['title' => 'Lista 3', 'due_on' => '2026-11-10']);
    item(['title' => 'Seminário', 'due_on' => '2026-11-19']);
    item(['title' => 'TCC', 'due_on' => '2027-03-01']);

    Livewire::test(Agenda::class)
        ->assertSeeInOrder(['Atrasados', 'Lista 3', 'Próximos 7 dias', 'Seminário', 'Mais adiante', 'TCC'])
        ->call('toggleDone', $late->id)
        ->assertSee('Feitos recentemente')
        ->call('setView', 'mes')->assertSee('Novembro de 2026')
        ->call('shiftMonth', 1)->assertSee('Dezembro de 2026');
});

it('disciplina mostra avaliações e a situação', function () {
    item(['title' => 'P1', 'grade' => 9, 'max_grade' => 10]);
    item(['title' => 'P2']);

    Livewire::test(SubjectShow::class, ['subject' => $this->subject])
        ->assertSee('Avaliações e prazos')->assertSee('P1')->assertSee('Precisa de 5 (de 10)');
});

it('prazo próximo vira sugestão e aparece na tela Hoje', function () {
    item(['title' => 'Prova de regressão', 'due_on' => '2026-11-18']);

    $s = app(SuggestionService::class)->forToday(Week::of(now(), 1));
    expect($s[0]['reason'])->toBe('Prazo amanhã')->and($s[0]['detail'])->toBe('Prova de regressão');

    $this->get('/')->assertSee('Próximos prazos')->assertSee('Prova de regressão');
});

it('não deixa usar disciplina de outro curso', function () {
    $other = Course::factory()->create(['user_id' => $this->user->id]);

    Livewire::test(AgendaForm::class)
        ->call('open', null, $other->id)
        ->set('subjectId', $this->subject->id)
        ->set('title', 'X')
        ->call('save');
})->throws(ModelNotFoundException::class);
