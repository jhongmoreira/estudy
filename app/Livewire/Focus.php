<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithUser;
use App\Models\ActiveTimer;
use App\Models\Review;
use App\Models\StudySession;
use App\Services\SessionService;
use App\Services\TimerService;
use App\Support\Duration;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Modo foco: relógio em tela cheia, pausar, Pomodoro e a folha "Como foi?". */
#[Title('Foco')]
#[Layout('components.layouts.focus')]
class Focus extends Component
{
    use WithUser;

    /**
     * Estado do relógio para o Alpine (milissegundos).
     *
     * @var array<string, int|null>
     */
    public array $clock = [];

    public string $notes = '';

    // Folha "Como foi?"
    public bool $finishing = false;

    public bool $forgotten = false;

    public string $duration = '';

    public string $kind = 'theory';

    public ?int $topicId = null;

    public ?int $pages = null;

    public ?int $questionsTotal = null;

    public ?int $questionsCorrect = null;

    public bool $markDone = false;

    public string $reviewRating = 'good';

    public function mount(TimerService $timers): void
    {
        $timer = $timers->current();
        if ($timer === null) {
            $this->redirectRoute('study', navigate: true);

            return;
        }
        $this->clock = $timer->clientState();
        $this->notes = (string) $timer->notes;
        $this->kind = $timer->kind;
        $this->topicId = $timer->topic_id;
    }

    public function pause(TimerService $timers): void
    {
        $timer = $this->timer();
        $timers->pause($timer);
        $this->clock = $timer->refresh()->clientState();
    }

    public function resume(TimerService $timers): void
    {
        $timer = $this->timer();
        $timers->resume($timer);
        $this->clock = $timer->refresh()->clientState();
        $this->finishing = false;
    }

    public function updatedNotes(): void
    {
        $this->timer()->update(['notes' => mb_substr($this->notes, 0, 10000)]);
    }

    /** Encerrar: congela o tempo e abre a folha "Como foi?". */
    public function finish(TimerService $timers): void
    {
        $timer = $this->timer();
        $frozen = $timers->freeze($timer);
        $this->clock = $timer->refresh()->clientState();
        $this->duration = Duration::human(max(StudySession::MIN_SECONDS, $frozen['seconds']));
        $this->forgotten = $frozen['forgotten'];
        $this->finishing = true;
        $this->resetValidation();
        $this->dispatch('open-sheet', 'finish');
    }

    public function save(SessionService $sessions): void
    {
        $this->resetValidation();
        $timer = $this->timer();
        $seconds = Duration::parse($this->duration);
        if ($seconds === null) {
            throw ValidationException::withMessages(['duration' => 'Não entendi a duração. Use, por exemplo, 45 min ou 1h30.']);
        }

        try {
            $session = $sessions->record([
                'subject_id' => $timer->subject_id,
                'topic_id' => $this->topicId,
                'date' => $timer->started_at->toDateString(),
                'started_at' => $timer->started_at,
                'duration_seconds' => $seconds,
                'kind' => $this->kind,
                'pages' => $this->pages,
                'questions_total' => $this->questionsTotal,
                'questions_correct' => $this->questionsCorrect,
                'notes' => $this->notes,
                'mark_done' => $this->markDone,
                'review_rating' => $this->reviewRating,
            ], $timer);
        } catch (ValidationException $e) {
            $errors = collect($e->errors())->mapWithKeys(fn ($m, $k) => [match ($k) {
                'duration_seconds' => 'duration', 'questions_total' => 'questionsTotal', 'questions_correct' => 'questionsCorrect', default => $k,
            } => $m])->all();
            throw ValidationException::withMessages($errors);
        }

        session()->flash('toast', 'Sessão salva: '.Duration::human($session->duration_seconds).' de '.$timer->subject->name.'.');
        $this->redirectRoute('home', navigate: true);
    }

    public function discard(TimerService $timers): void
    {
        $timers->discard($this->timer());
        session()->flash('toast', 'Cronômetro descartado.');
        $this->redirectRoute('home', navigate: true);
    }

    private function timer(): ActiveTimer
    {
        return ActiveTimer::with(['subject.course', 'topic'])->firstOrFail();
    }

    public function render(SessionService $sessions): View
    {
        $timer = ActiveTimer::with(['subject.course', 'topic'])->first();
        $today = CarbonImmutable::today();

        return view('livewire.focus', [
            'timer' => $timer,
            'topics' => $timer ? $timer->subject->topics()->get() : collect(),
            'pendingReview' => $this->topicId ? Review::pending()->where('topic_id', $this->topicId)->first() : null,
            'pomodoroConfig' => $this->user()->pomodoro(),
            'goalSeconds' => $this->user()->dailyGoalMinutes() * 60,
            'todaySeconds' => $sessions->dailyTotals($today, $today)[$today->toDateString()] ?? 0,
        ]);
    }
}
