<?php

namespace App\Livewire;

use App\Models\Review;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\Topic;
use App\Services\ProgressService;
use App\Services\ReviewService;
use App\Support\Reorder;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

class SubjectShow extends Component
{
    public Subject $subject;

    #[Url(as: 'filtro', except: 'all')]
    public string $filter = 'all';

    public bool $organizing = false;

    public string $newTopic = '';

    // Edição de tópico (folha)
    public ?int $topicId = null;

    public string $topicTitle = '';

    public string $topicStatus = 'todo';

    public ?int $topicDifficulty = null;

    public ?int $topicEstimated = null;

    public string $topicUrl = '';

    public string $topicNotes = '';

    public function mount(): void
    {
        $this->subject->load('course');
    }

    public function addTopic(): void
    {
        $title = trim($this->newTopic);
        $this->validate(['newTopic' => ['required', 'string', 'max:200']], attributes: ['newTopic' => 'tópico']);

        $this->subject->topics()->create([
            'title' => $title,
            'position' => (int) $this->subject->topics()->max('position') + 1,
        ]);
        $this->newTopic = '';
    }

    public function toggle(int $id): void
    {
        $this->topic($id)->toggleDone();
    }

    public function setDifficulty(int $id, int $level): void
    {
        $topic = $this->topic($id);
        $topic->update(['difficulty' => $topic->difficulty === $level ? null : max(1, min(3, $level))]);
    }

    public function move(int $id, int $direction): void
    {
        Reorder::move(Topic::query()->where('subject_id', $this->subject->id)->orderBy('position')->orderBy('id'), $id, $direction);
    }

    public function editTopic(int $id): void
    {
        $topic = $this->topic($id);
        $this->resetValidation();
        $this->topicId = $topic->id;
        $this->topicTitle = $topic->title;
        $this->topicStatus = $topic->status;
        $this->topicDifficulty = $topic->difficulty;
        $this->topicEstimated = $topic->estimated_min;
        $this->topicUrl = (string) $topic->resource_url;
        $this->topicNotes = (string) $topic->notes;
        $this->dispatch('open-sheet', 'topic-form');
    }

    public function saveTopic(): void
    {
        $this->validate([
            'topicTitle' => ['required', 'string', 'max:200'],
            'topicStatus' => ['required', Rule::in(array_keys(Topic::STATUSES))],
            'topicDifficulty' => ['nullable', 'integer', 'between:1,3'],
            'topicEstimated' => ['nullable', 'integer', 'min:1', 'max:6000'],
            'topicUrl' => ['nullable', 'url', 'max:500'],
            'topicNotes' => ['nullable', 'string', 'max:20000'],
        ], attributes: ['topicTitle' => 'título', 'topicEstimated' => 'estimativa', 'topicUrl' => 'link', 'topicNotes' => 'anotações']);

        $topic = $this->topic((int) $this->topicId);
        $wasDone = $topic->isDone();
        $topic->update([
            'title' => trim($this->topicTitle),
            'status' => $this->topicStatus,
            'difficulty' => $this->topicDifficulty,
            'estimated_min' => $this->topicEstimated,
            'resource_url' => $this->topicUrl ?: null,
            'notes' => $this->topicNotes ?: null,
            'done_on' => $this->topicStatus === 'done' ? ($wasDone ? $topic->done_on : today()) : null,
        ]);

        $this->dispatch('close-sheet', 'topic-form');
        $this->dispatch('toast', message: 'Tópico salvo.');
    }

    public function reviewTopicOn(string $when, ReviewService $reviews): void
    {
        $today = CarbonImmutable::today();
        $date = match ($when) {
            'today' => $today,
            'tomorrow' => $today->addDay(),
            default => $today->addWeek(),
        };
        $reviews->scheduleManually($this->topic((int) $this->topicId), $date);
        $this->dispatch('toast', message: 'Revisão agendada para '.$date->translatedFormat('j \\d\\e M').'.');
    }

    public function deleteTopic(): void
    {
        $this->topic((int) $this->topicId)->delete();
        $this->dispatch('close-sheet', 'topic-form');
        $this->dispatch('toast', message: 'Tópico excluído.');
    }

    public function deleteSubject(): void
    {
        $course = $this->subject->course;
        $this->subject->delete();
        session()->flash('toast', $course->unitLabel() === 'Disciplina' ? 'Disciplina excluída.' : 'Módulo excluído.');
        $this->redirectRoute('courses.show', $course, navigate: true);
    }

    #[On('subject-saved')]
    #[On('session-saved')]
    public function refreshSubject(): void
    {
        $this->subject->refresh()->load('course');
    }

    private function topic(int $id): Topic
    {
        return Topic::where('subject_id', $this->subject->id)->findOrFail($id);
    }

    public function render(ProgressService $progress): View
    {
        $query = $this->subject->topics();
        match ($this->filter) {
            'todo' => $query->where('status', '!=', 'done'),
            'done' => $query->where('status', 'done'),
            'hard' => $query->where('difficulty', 3),
            default => null,
        };

        return view('livewire.subject-show', [
            'topics' => $query->get(),
            'stats' => $progress->forSubject($this->subject),
            'course' => $this->subject->course,
            'color' => $this->subject->displayColor(),
            'topicReview' => $this->topicId ? Review::pending()->where('topic_id', $this->topicId)->first() : null,
            'recentSessions' => StudySession::with('topic')->where('subject_id', $this->subject->id)->orderByDesc('date')->orderByDesc('id')->limit(5)->get(),
            'sessionTotal' => (int) StudySession::where('subject_id', $this->subject->id)->sum('duration_seconds'),
            'sessionCount' => StudySession::where('subject_id', $this->subject->id)->count(),
        ])->title($this->subject->name);
    }
}
