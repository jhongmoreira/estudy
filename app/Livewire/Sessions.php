<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\StudySession;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Histórico de sessões, agrupado por dia. */
#[Title('Sessões')]
class Sessions extends Component
{
    public const PERIODS = ['7' => '7 dias', '30' => '30 dias', '90' => '90 dias', 'all' => 'Tudo'];

    #[Url(as: 'periodo', except: '30')]
    public string $period = '30';

    #[Url(as: 'curso', except: null)]
    public ?int $courseId = null;

    #[Url(as: 'tipo', except: '')]
    public string $kind = '';

    public int $limit = 60;

    public function updated(string $property): void
    {
        if (in_array($property, ['period', 'courseId', 'kind'], true)) {
            $this->limit = 60;
            $this->validate([
                'period' => [Rule::in(array_keys(self::PERIODS))],
                'kind' => ['nullable', Rule::in(array_keys(StudySession::KINDS))],
            ]);
        }
    }

    public function more(): void
    {
        $this->limit += 60;
    }

    #[On('session-saved')]
    public function refreshList(): void {}

    /** @return Builder<StudySession> */
    private function query(): Builder
    {
        return StudySession::query()
            ->when($this->period !== 'all', fn ($q) => $q->where('date', '>=', CarbonImmutable::today()->subDays((int) $this->period - 1)->toDateString()))
            ->when($this->courseId, fn ($q) => $q->where('course_id', $this->courseId))
            ->when($this->kind !== '', fn ($q) => $q->where('kind', $this->kind));
    }

    public function render(): View
    {
        $sessions = $this->query()->with(['subject.course', 'topic'])
            ->orderByDesc('date')->orderByDesc('started_at')->orderByDesc('id')
            ->limit($this->limit + 1)->get();

        $hasMore = $sessions->count() > $this->limit;
        $sessions = $sessions->take($this->limit);

        return view('livewire.sessions', [
            'days' => $sessions->groupBy(fn (StudySession $s) => $s->date->toDateString()),
            'dayTotals' => $this->query()->selectRaw('date, SUM(duration_seconds) as total')->groupBy('date')->pluck('total', 'date'),
            'total' => (int) $this->query()->sum('duration_seconds'),
            'count' => $this->query()->count(),
            'hasMore' => $hasMore,
            'courses' => Course::query()->ordered()->get(['id', 'name']),
        ]);
    }
}
