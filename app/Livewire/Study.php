<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithUser;
use App\Models\ActiveTimer;
use App\Models\Course;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\Topic;
use App\Services\CycleService;
use App\Services\TimerService;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Escolher o que estudar e ligar o cronômetro. */
#[Title('Estudar')]
class Study extends Component
{
    use WithUser;

    #[Url(as: 'curso', except: null)]
    public ?int $courseId = null;

    #[Url(as: 'disciplina', except: null)]
    public ?int $subjectId = null;

    #[Url(as: 'topico', except: null)]
    public ?int $topicId = null;

    #[Url(as: 'tipo', except: 'theory')]
    public string $kind = 'theory';

    public bool $pomodoro = false;

    public function mount(): void
    {
        if (ActiveTimer::query()->exists()) {
            $this->redirectRoute('focus', navigate: true);

            return;
        }

        $this->pomodoro = (bool) session('study.pomodoro', false);

        if ($this->topicId !== null) {
            $topic = Topic::find($this->topicId);
            $this->subjectId = $topic?->subject_id;
            $this->topicId = $topic?->id;
        }
        $kind = $this->kind;
        if ($this->subjectId !== null) {
            $this->chooseSubject($this->subjectId, keepTopic: true);
        }
        if ($kind !== 'theory' && array_key_exists($kind, StudySession::KINDS)) {
            $this->kind = $kind;
        }
    }

    public function chooseSubject(int $id, bool $keepTopic = false): void
    {
        $subject = Subject::find($id);
        $this->subjectId = $subject?->id;
        if (! $keepTopic) {
            $this->topicId = null;
        }
        // Tipo padrão = o último usado nesta disciplina
        $this->kind = (string) (StudySession::where('subject_id', $id)->latest('id')->value('kind') ?? $this->kind);
    }

    public function chooseTopic(?int $id): void
    {
        $this->topicId = $id !== null && Topic::where('subject_id', $this->subjectId)->whereKey($id)->exists() ? $id : null;
    }

    public function continueLast(int $subjectId, ?int $topicId = null): void
    {
        $this->chooseSubject($subjectId);
        $this->chooseTopic($topicId);
    }

    public function clearSubject(): void
    {
        $this->subjectId = null;
        $this->topicId = null;
    }

    public function start(TimerService $timers): void
    {
        $this->validate([
            'subjectId' => ['required', 'integer'],
            'kind' => ['required', Rule::in(array_keys(StudySession::KINDS))],
        ], ['subjectId.required' => 'Escolha o que vai estudar.']);

        $subject = Subject::findOrFail($this->subjectId);
        $topic = $this->topicId ? Topic::where('subject_id', $subject->id)->find($this->topicId) : null;

        session(['study.pomodoro' => $this->pomodoro]);
        $timers->start($subject, $topic, $this->kind, $this->pomodoro);
        $this->redirectRoute('focus', navigate: true);
    }

    public function render(): View
    {
        $courses = Course::query()
            ->where('status', 'active')
            ->when($this->courseId, fn ($q) => $q->whereKey($this->courseId))
            ->with(['subjects' => fn ($q) => $q->where('status', 'active')])
            ->ordered()
            ->get();

        $last = StudySession::with(['subject.course', 'topic'])->latest('date')->latest('id')->first();
        $subject = $this->subjectId ? Subject::with('course')->find($this->subjectId) : null;

        $cycle = app(CycleService::class)->active();

        return view('livewire.study', [
            'cycleItem' => $cycle?->currentItem(),
            'courses' => $courses,
            'last' => $last,
            'subject' => $subject,
            'topics' => $subject ? $subject->topics()->where('status', '!=', 'done')->get() : collect(),
            'pomodoroConfig' => $this->user()->pomodoro(),
        ]);
    }
}
