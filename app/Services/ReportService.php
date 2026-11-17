<?php

namespace App\Services;

use App\Models\AgendaItem;
use App\Models\Course;
use App\Models\Review;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\User;
use Carbon\CarbonImmutable;

/** Dados dos relatórios em PDF (separado do controller para ser testável). */
class ReportService
{
    public function __construct(
        private StatsService $stats,
        private GradeService $grades,
        private ProgressService $progress,
        private StreakService $streaks,
    ) {}

    /** @return array<string, mixed> */
    public function study(User $user, CarbonImmutable $from, CarbonImmutable $to, ?Course $course, bool $withSessions): array
    {
        $s = $this->stats->scopeRange($from, $to, $course?->id);
        $summary = $s->summary();
        $days = max(1, (int) $from->diffInDays($to) + 1);

        $byWeek = StudySession::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->when($course, fn ($q) => $q->where('course_id', $course->id))
            ->selectRaw("strftime('%Y-%W', date) as wk, MIN(date) as first_day, SUM(duration_seconds) as total, COUNT(DISTINCT date) as days")
            ->groupBy('wk')->orderBy('wk')->toBase()->get()
            ->map(fn ($r) => ['label' => CarbonImmutable::parse($r->first_day)->startOfWeek()->format('d/m'), 'total' => (int) $r->total, 'days' => (int) $r->days])->all();

        return [
            'user' => $user,
            'from' => $from,
            'to' => $to,
            'course' => $course,
            'summary' => $summary,
            'goalSeconds' => $user->dailyGoalMinutes() * 60,
            'periodDays' => $days,
            'streak' => $this->streaks->compute($user),
            'byCourse' => $s->byCourse(),
            'bySubject' => $s->bySubject(15),
            'byKind' => $s->byKind(),
            'byWeek' => $byWeek,
            'dayParts' => $s->byDayPart(),
            'questions' => $s->questions(),
            'reading' => $s->reading(),
            'reviews' => $s->reviews(),
            'sessions' => $withSessions
                ? StudySession::with(['subject', 'topic'])->whereBetween('date', [$from->toDateString(), $to->toDateString()])
                    ->when($course, fn ($q) => $q->where('course_id', $course->id))
                    ->orderBy('date')->orderBy('started_at')->limit(1500)->get()
                : null,
        ];
    }

    /** @return array<string, mixed> */
    public function grades(User $user, Course $course): array
    {
        $subjects = $course->subjects()->get();
        $items = AgendaItem::where('course_id', $course->id)->orderBy('due_on')->get()->groupBy('subject_id');

        $rows = $subjects->map(fn (Subject $subject) => [
            'subject' => $subject,
            'items' => $items->get($subject->id, collect()),
            'summary' => $this->grades->summary($subject, $items->get($subject->id, collect())),
        ])->filter(fn ($r) => $r['items']->isNotEmpty() || $r['subject']->passing_grade !== null)->values();

        return [
            'user' => $user,
            'course' => $course,
            'rows' => $rows,
            'loose' => $items->get('', collect())->merge($items->get(null, collect())),
            'groups' => $rows->groupBy(fn ($r) => $r['subject']->term ?? ''),
        ];
    }

    /** @return array<string, mixed> */
    public function course(User $user, Course $course): array
    {
        $subjects = $course->subjects()->with('topics')->get();
        $time = StudySession::where('course_id', $course->id)->selectRaw('subject_id, SUM(duration_seconds) as total, MAX(date) as last')
            ->groupBy('subject_id')->get()->keyBy('subject_id');
        $pendingReviews = Review::pending()->whereIn('subject_id', $subjects->pluck('id'))->get()->keyBy('topic_id');

        return [
            'user' => $user,
            'course' => $course,
            'subjects' => $subjects,
            'stats' => $this->progress->forSubjects($subjects->pluck('id')->all()),
            'courseStats' => $this->progress->forCourse($course),
            'time' => $time,
            'totalSeconds' => (int) $time->sum('total'),
            'reviews' => $pendingReviews,
            'agenda' => AgendaItem::with('subject')->where('course_id', $course->id)->where('status', 'open')->orderBy('due_on')->get(),
        ];
    }

    /** @return array<string, mixed> */
    public function formation(User $user, bool $includeInProgress): array
    {
        $hours = StudySession::query()->selectRaw('course_id, SUM(duration_seconds) as total')->groupBy('course_id')->pluck('total', 'course_id');
        $statuses = $includeInProgress ? ['completed', 'active', 'paused'] : ['completed'];
        $courses = Course::with('certificate')->whereIn('status', $statuses)
            ->orderByRaw("CASE WHEN status = 'completed' THEN 0 ELSE 1 END")->orderByDesc('completed_on')->orderBy('position')->get();

        $hoursOf = fn (Course $c) => $c->workload_hours ?? intdiv((int) ($hours[$c->id] ?? 0), 3600);
        $completed = $courses->where('status', 'completed');

        return [
            'user' => $user,
            'courses' => $courses,
            'hours' => $hours,
            'hoursOf' => $hoursOf,
            'byType' => $completed->groupBy('type')->map(fn ($g) => ['count' => $g->count(), 'hours' => (int) $g->sum($hoursOf)]),
            'byYear' => $completed->filter(fn (Course $c) => $c->completed_on !== null)->groupBy(fn (Course $c) => $c->completed_on?->year)->map(fn ($g) => (int) $g->sum($hoursOf))->sortKeysDesc(),
            'totalHours' => (int) $completed->sum($hoursOf),
            'loggedHours' => intdiv((int) $hours->sum(), 3600),
        ];
    }
}
