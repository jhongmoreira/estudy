<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithUser;
use App\Models\ActiveTimer;
use App\Models\AgendaItem;
use App\Models\Course;
use App\Models\Review;
use App\Services\GoalService;
use App\Services\ProgressService;
use App\Services\SessionService;
use App\Services\StreakService;
use App\Services\SuggestionService;
use App\Support\Week;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Hoje')]
class Today extends Component
{
    use WithUser;

    #[On('session-saved')]
    #[On('agenda-saved')]
    public function refreshToday(): void {}

    public function render(ProgressService $progress, SessionService $sessions, StreakService $streaks, SuggestionService $suggestions, GoalService $goals): View
    {
        $user = $this->user();
        $now = CarbonImmutable::now();
        $hour = (int) $now->format('G');
        $greeting = match (true) {
            $hour < 5 => 'Boa madrugada',
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };

        $week = Week::of($now, $user->weekStartsOn());
        $heatStart = $week->end->subDays(8 * 7 - 1);
        $daily = $sessions->dailyTotals($heatStart, $week->end);
        $courses = Course::query()->where('status', 'active')->ordered()->limit(6)->get();

        return view('livewire.today', [
            'user' => $user,
            'greeting' => $greeting,
            'today' => $now,
            'goalMinutes' => $user->dailyGoalMinutes(),
            'todaySeconds' => $daily[$now->toDateString()] ?? 0,
            'weekSeconds' => array_sum(array_filter($daily, fn ($k) => $k >= $week->start->toDateString(), ARRAY_FILTER_USE_KEY)),
            'week' => $week,
            'daily' => $daily,
            'streak' => $streaks->for($user),
            'suggestions' => $suggestions->forToday($week),
            'running' => ActiveTimer::with('subject')->first(),
            'dueReviews' => Review::due()->with(['topic', 'subject.course'])->orderBy('due_on')->limit(5)->get(),
            'dueCount' => Review::due()->count(),
            'agenda' => AgendaItem::with(['course', 'subject'])->where('status', 'open')
                ->where('due_on', '<=', $now->addDays(7)->toDateString())->orderBy('due_on')->limit(5)->get(),
            'unread' => $user->unreadNotifications()->count(),
            'hasPush' => $user->pushSubscriptions()->exists(),
            'courses' => $courses,
            'courseStats' => $progress->forCourses($courses),
            'courseWeek' => $goals->weekByCourse($week),
        ]);
    }
}
