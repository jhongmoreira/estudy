<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithUser;
use App\Models\Course;
use App\Services\SessionService;
use App\Services\StatsService;
use App\Services\StreakService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Estatísticas')]
class Stats extends Component
{
    use WithUser;

    #[Url(as: 'periodo', except: '30')]
    public string $period = '30';

    #[Url(as: 'curso', except: null)]
    public ?int $courseId = null;

    public function render(StatsService $stats, StreakService $streaks, SessionService $sessions): View
    {
        if (! array_key_exists($this->period, StatsService::PERIODS)) {
            $this->period = '30';
        }
        $user = $this->user();
        $s = $stats->scope($this->period, $this->courseId);
        $summary = $s->summary();
        $today = CarbonImmutable::today();

        return view('livewire.stats', [
            'user' => $user,
            'summary' => $summary,
            'goalPct' => $summary['days'] > 0 ? (int) round($summary['avg_per_day'] / ($user->dailyGoalMinutes() * 60) * 100) : 0,
            'streak' => $streaks->for($user),
            'timeline' => $s->timeline($user->weekStartsOn()),
            'byCourse' => $s->byCourse(),
            'bySubject' => $s->bySubject(),
            'byKind' => $s->byKind(),
            'dayParts' => $s->byDayPart(),
            'weekdays' => $s->byWeekday(),
            'questions' => $s->questions(),
            'reading' => $s->reading(),
            'reviews' => $s->reviews(),
            'difficulty' => $s->difficulty(),
            'yearTotals' => $sessions->dailyTotals($today->subDays(370), $today),
            'courses' => Course::query()->ordered()->get(['id', 'name']),
        ]);
    }
}
