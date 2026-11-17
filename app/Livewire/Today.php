<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithUser;
use App\Models\ActiveTimer;
use App\Models\Course;
use App\Models\Review;
use App\Services\ProgressService;
use App\Services\SessionService;
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
    public function refreshToday(): void {}

    public function render(ProgressService $progress, SessionService $sessions): View
    {
        $user = $this->user();
        $courses = Course::query()->where('status', 'active')->ordered()->limit(6)->get();
        $now = CarbonImmutable::now();
        $hour = (int) $now->format('G');

        $greeting = match (true) {
            $hour < 5 => 'Boa madrugada',
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };

        $week = Week::of($now, $user->weekStartsOn());
        $daily = $sessions->dailyTotals($week->start, $week->end);

        return view('livewire.today', [
            'daily' => $daily,
            'running' => ActiveTimer::with('subject')->first(),
            'dueReviews' => Review::due()->with(['topic', 'subject.course'])->orderBy('due_on')->limit(5)->get(),
            'dueCount' => Review::due()->count(),
            'user' => $user,
            'greeting' => $greeting,
            'today' => $now,
            'goalMinutes' => $user->dailyGoalMinutes(),
            'todaySeconds' => $daily[$now->toDateString()] ?? 0,
            'week' => $week,
            'courses' => $courses,
            'courseStats' => $progress->forCourses($courses),
        ]);
    }
}
