<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\StudySession;
use App\Models\Subject;
use App\Services\SessionService;
use App\Support\Duration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Registro manual e edição de sessões.
 * Abrir: $dispatch('log-session', { subjectId: null|5, id: null|12 })
 */
class SessionForm extends Component
{
    public ?int $sessionId = null;

    public ?int $subjectId = null;

    public ?int $topicId = null;

    public string $date = '';

    public string $time = '';

    public string $duration = '';

    public string $kind = 'theory';

    public ?int $pages = null;

    public ?int $questionsTotal = null;

    public ?int $questionsCorrect = null;

    public string $notes = '';

    public bool $markDone = false;

    #[On('log-session')]
    public function open(?int $subjectId = null, ?int $id = null): void
    {
        $this->resetValidation();
        $this->reset();
        $this->date = CarbonImmutable::today()->toDateString();

        if ($id !== null) {
            $s = StudySession::findOrFail($id);
            $this->fill([
                'sessionId' => $s->id,
                'subjectId' => $s->subject_id,
                'topicId' => $s->topic_id,
                'date' => $s->date->toDateString(),
                'time' => $s->started_at?->format('H:i') ?? '',
                'duration' => Duration::human($s->duration_seconds),
                'kind' => $s->kind,
                'pages' => $s->pages,
                'questionsTotal' => $s->questions_total,
                'questionsCorrect' => $s->questions_correct,
                'notes' => (string) $s->notes,
            ]);
        } else {
            $this->subjectId = $subjectId ?? StudySession::latest('id')->value('subject_id');
        }

        $this->dispatch('open-sheet', 'session-form');
    }

    public function updatedSubjectId(): void
    {
        $this->topicId = null;
    }

    public function setDuration(int $minutes): void
    {
        $this->duration = Duration::human($minutes * 60);
    }

    public function save(SessionService $sessions): void
    {
        $this->resetValidation();
        $seconds = Duration::parse($this->duration);
        if ($seconds === null) {
            throw ValidationException::withMessages(['duration' => 'Não entendi a duração. Use, por exemplo, 45 min ou 1h30.']);
        }
        if ($this->subjectId === null) {
            throw ValidationException::withMessages(['subjectId' => 'Escolha a disciplina ou módulo.']);
        }

        $startedAt = null;
        if (preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $this->time) && $this->date !== '') {
            $startedAt = CarbonImmutable::parse($this->date.' '.$this->time);
        }

        try {
            $sessions->record([
                'subject_id' => $this->subjectId,
                'topic_id' => $this->topicId,
                'date' => $this->date,
                'started_at' => $startedAt,
                'duration_seconds' => $seconds,
                'kind' => $this->kind,
                'pages' => $this->pages,
                'questions_total' => $this->questionsTotal,
                'questions_correct' => $this->questionsCorrect,
                'notes' => $this->notes,
                'mark_done' => $this->markDone,
            ], existing: $this->sessionId ? StudySession::findOrFail($this->sessionId) : null);
        } catch (ValidationException $e) {
            throw ValidationException::withMessages(collect($e->errors())->mapWithKeys(fn ($m, $k) => [match ($k) {
                'duration_seconds' => 'duration', 'questions_total' => 'questionsTotal', 'questions_correct' => 'questionsCorrect',
                'subject_id' => 'subjectId', 'topic_id' => 'topicId', default => $k,
            } => $m])->all());
        }

        $this->dispatch('close-sheet', 'session-form');
        $this->dispatch('session-saved');
        $this->dispatch('toast', message: $this->sessionId ? 'Sessão atualizada.' : 'Estudo registrado: '.Duration::human($seconds).'.');
    }

    public function delete(): void
    {
        if ($this->sessionId !== null) {
            StudySession::findOrFail($this->sessionId)->delete();
            $this->dispatch('close-sheet', 'session-form');
            $this->dispatch('session-saved');
            $this->dispatch('toast', message: 'Sessão excluída.');
        }
    }

    public function render(): View
    {
        $courses = Course::query()->whereIn('status', ['active', 'paused', 'planned'])
            ->with(['subjects' => fn ($q) => $q->where('status', '!=', 'archived')])->ordered()->get();

        // Ao editar uma sessão de curso concluído, a disciplina também precisa aparecer
        if ($this->subjectId && ! $courses->flatMap->subjects->contains('id', $this->subjectId)) {
            $extra = Subject::with('course')->find($this->subjectId);
            if ($extra) {
                $extra->course->setRelation('subjects', collect([$extra]));
                $courses->push($extra->course);
            }
        }

        $subject = $this->subjectId ? Subject::find($this->subjectId) : null;

        return view('livewire.session-form', [
            'courses' => $courses,
            'topics' => $subject ? $subject->topics()->get() : collect(),
        ]);
    }
}
