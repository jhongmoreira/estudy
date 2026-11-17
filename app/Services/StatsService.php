<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Review;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\Topic;
use App\Support\Week;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/** Estatísticas agregadas (docs/etapas/07). Tudo em consultas GROUP BY sobre study_sessions. */
class StatsService
{
    public const PERIODS = ['7' => '7 dias', '30' => '30 dias', '90' => '3 meses', '365' => '12 meses', 'all' => 'Tudo'];

    private CarbonImmutable $from;

    private CarbonImmutable $to;

    private ?int $courseId = null;

    public function scope(string $period, ?int $courseId): static
    {
        $this->to = CarbonImmutable::today();
        $this->from = $period === 'all'
            ? CarbonImmutable::parse(StudySession::min('date') ?? $this->to->toDateString())
            : $this->to->subDays(max(1, (int) $period) - 1);
        $this->courseId = $courseId;

        return $this;
    }

    /** @return Builder<StudySession> */
    private function sessions(): Builder
    {
        return StudySession::query()
            ->whereBetween('date', [$this->from->toDateString(), $this->to->toDateString()])
            ->when($this->courseId, fn ($q) => $q->where('course_id', $this->courseId));
    }

    /** @return array{seconds: int, sessions: int, days: int, avg_per_day: int, from: CarbonImmutable, to: CarbonImmutable} */
    public function summary(): array
    {
        $row = $this->sessions()->selectRaw('COALESCE(SUM(duration_seconds),0) as s, COUNT(*) as n, COUNT(DISTINCT date) as d')->toBase()->first();
        $days = (int) ($row->d ?? 0);

        return [
            'seconds' => (int) ($row->s ?? 0),
            'sessions' => (int) ($row->n ?? 0),
            'days' => $days,
            'avg_per_day' => $days > 0 ? intdiv((int) $row->s, $days) : 0,
            'from' => $this->from,
            'to' => $this->to,
        ];
    }

    /**
     * Série temporal empilhada por curso: por dia (até 31 dias) ou por semana.
     *
     * @return array{bucket: string, bars: list<array{label: string, title: string, total: int, segments: list<array{value: int, color: string, name: string}>}>}
     */
    public function timeline(int $weekStartsOn = 1): array
    {
        $byWeek = $this->from->diffInDays($this->to) > 31;
        $courses = Course::query()->get(['id', 'name', 'color'])->keyBy('id');
        $rows = $this->sessions()->selectRaw('date, course_id, SUM(duration_seconds) as total')->groupBy('date', 'course_id')->toBase()->get();

        $buckets = [];
        if ($byWeek) {
            for ($w = Week::of($this->from, $weekStartsOn)->start; $w->lte($this->to); $w = $w->addWeek()) {
                $buckets[$w->toDateString()] = ['label' => $w->format('d/m'), 'title' => 'Semana de '.$w->translatedFormat('j \d\e M'), 'data' => []];
            }
        } else {
            for ($d = $this->from; $d->lte($this->to); $d = $d->addDay()) {
                $buckets[$d->toDateString()] = ['label' => $d->format('d'), 'title' => Str::ucfirst($d->translatedFormat('D, j \d\e M')), 'data' => []];
            }
        }

        foreach ($rows as $row) {
            $key = $byWeek ? Week::of(CarbonImmutable::parse($row->date), $weekStartsOn)->start->toDateString() : $row->date;
            if (isset($buckets[$key])) {
                $buckets[$key]['data'][$row->course_id] = ($buckets[$key]['data'][$row->course_id] ?? 0) + (int) $row->total;
            }
        }

        $bars = [];
        foreach ($buckets as $b) {
            $segments = [];
            foreach ($b['data'] as $courseId => $value) {
                $c = $courses->get($courseId);
                $segments[] = ['value' => $value, 'color' => $c->color ?? '#64748b', 'name' => $c->name ?? '—'];
            }
            $bars[] = ['label' => $b['label'], 'title' => $b['title'], 'total' => array_sum($b['data']), 'segments' => $segments];
        }

        return ['bucket' => $byWeek ? 'semana' : 'dia', 'bars' => $bars];
    }

    /** @return list<array{name: string, color: string, value: int}> */
    public function byCourse(): array
    {
        $courses = Course::query()->get(['id', 'name', 'color'])->keyBy('id');

        return $this->sessions()->selectRaw('course_id, SUM(duration_seconds) as total')->groupBy('course_id')->orderByDesc('total')->toBase()->get()
            ->map(fn ($r) => ['name' => $courses[$r->course_id]->name ?? '—', 'color' => $courses[$r->course_id]->color ?? '#64748b', 'value' => (int) $r->total])
            ->values()->all();
    }

    /** @return list<array{name: string, detail: string, color: string, value: int}> */
    public function bySubject(int $limit = 10): array
    {
        $rows = $this->sessions()->selectRaw('subject_id, SUM(duration_seconds) as total')->groupBy('subject_id')->orderByDesc('total')->limit($limit)->toBase()->get();
        $subjects = Subject::with('course')->whereIn('id', $rows->pluck('subject_id'))->get()->keyBy('id');

        return $rows->map(function ($r) use ($subjects) {
            $s = $subjects->get($r->subject_id);

            return ['name' => $s->name ?? '—', 'detail' => $s?->course->name ?? '', 'color' => $s ? ($s->color ?? $s->course->color) : '#64748b', 'value' => (int) $r->total];
        })->values()->all();
    }

    /** @return list<array{name: string, icon: string, value: int}> */
    public function byKind(): array
    {
        return $this->sessions()->selectRaw('kind, SUM(duration_seconds) as total')->groupBy('kind')->orderByDesc('total')->toBase()->get()
            ->map(fn ($r) => ['name' => StudySession::KINDS[$r->kind][0] ?? $r->kind, 'icon' => StudySession::KINDS[$r->kind][1] ?? 'book', 'value' => (int) $r->total])
            ->values()->all();
    }

    /**
     * Rendimento por período do dia (sessões com horário).
     *
     * @return array<string, int>
     */
    public function byDayPart(): array
    {
        $parts = ['Madrugada' => 0, 'Manhã' => 0, 'Tarde' => 0, 'Noite' => 0];
        $rows = $this->sessions()->whereNotNull('started_at')
            ->selectRaw("CAST(strftime('%H', started_at) AS INTEGER) as h, SUM(duration_seconds) as total")->groupBy('h')->toBase()->get();
        foreach ($rows as $r) {
            $part = match (true) {
                $r->h < 5 => 'Madrugada',
                $r->h < 12 => 'Manhã',
                $r->h < 18 => 'Tarde',
                default => 'Noite',
            };
            $parts[$part] += (int) $r->total;
        }

        return $parts;
    }

    /** @return array<string, int> seg..dom */
    public function byWeekday(): array
    {
        $names = [1 => 'Seg', 2 => 'Ter', 3 => 'Qua', 4 => 'Qui', 5 => 'Sex', 6 => 'Sáb', 0 => 'Dom'];
        $rows = $this->sessions()->selectRaw("CAST(strftime('%w', date) AS INTEGER) as wd, SUM(duration_seconds) as total")->groupBy('wd')->pluck('total', 'wd');

        $out = [];
        foreach ($names as $wd => $label) {
            $out[$label] = (int) ($rows[$wd] ?? 0);
        }

        return $out;
    }

    /** @return array{total: int, correct: int, by_subject: list<array{name: string, color: string, total: int, correct: int}>} */
    public function questions(): array
    {
        $rows = $this->sessions()->whereNotNull('questions_total')->where('questions_total', '>', 0)
            ->selectRaw('subject_id, SUM(questions_total) as t, SUM(questions_correct) as c')->groupBy('subject_id')->orderByDesc('t')->toBase()->get();
        $subjects = Subject::with('course')->whereIn('id', $rows->pluck('subject_id'))->get()->keyBy('id');

        return [
            'total' => (int) $rows->sum('t'),
            'correct' => (int) $rows->sum('c'),
            'by_subject' => $rows->take(8)->map(fn ($r) => [
                'name' => $subjects[$r->subject_id]->name ?? '—',
                'color' => isset($subjects[$r->subject_id]) ? ($subjects[$r->subject_id]->color ?? $subjects[$r->subject_id]->course->color) : '#64748b',
                'total' => (int) $r->t, 'correct' => (int) $r->c,
            ])->values()->all(),
        ];
    }

    /** @return array{pages: int, per_hour: float|null} */
    public function reading(): array
    {
        $row = $this->sessions()->whereNotNull('pages')->where('pages', '>', 0)->selectRaw('SUM(pages) as p, SUM(duration_seconds) as s')->toBase()->first();
        $pages = (int) ($row->p ?? 0);
        $seconds = (int) ($row->s ?? 0);

        return ['pages' => $pages, 'per_hour' => $seconds > 0 ? round($pages / ($seconds / 3600), 1) : null];
    }

    /** @return array{done: int, on_time: int, ratings: array<string, int>, pending: int, overdue: int} */
    public function reviews(): array
    {
        $done = Review::where('status', 'done')->whereBetween('done_on', [$this->from->toDateString(), $this->to->toDateString()])
            ->when($this->courseId, fn ($q) => $q->whereHas('subject', fn ($s) => $s->where('course_id', $this->courseId)))->get(['rating', 'done_on', 'due_on']);

        return [
            'done' => $done->count(),
            'on_time' => $done->filter(fn (Review $r) => $r->done_on !== null && $r->done_on->lte($r->due_on))->count(),
            'ratings' => ['good' => $done->where('rating', 'good')->count(), 'hard' => $done->where('rating', 'hard')->count(), 'forgot' => $done->where('rating', 'forgot')->count()],
            'pending' => Review::pending()->count(),
            'overdue' => Review::pending()->where('due_on', '<', CarbonImmutable::today()->toDateString())->count(),
        ];
    }

    /**
     * Mapa de dificuldade por disciplina.
     *
     * @return list<array{name: string, color: string, easy: int, medium: int, hard: int, unrated: int, done: int, total: int, forgot: int}>
     */
    public function difficulty(): array
    {
        $rows = Topic::query()
            ->join('subjects', 'subjects.id', '=', 'topics.subject_id')
            ->when($this->courseId, fn ($q) => $q->where('subjects.course_id', $this->courseId))
            ->where('subjects.status', 'active')
            ->selectRaw('topics.subject_id as sid, COUNT(*) as total')
            ->selectRaw('SUM(CASE WHEN difficulty = 1 THEN 1 ELSE 0 END) as easy')
            ->selectRaw('SUM(CASE WHEN difficulty = 2 THEN 1 ELSE 0 END) as medium')
            ->selectRaw('SUM(CASE WHEN difficulty = 3 THEN 1 ELSE 0 END) as hard')
            ->selectRaw("SUM(CASE WHEN topics.status = 'done' THEN 1 ELSE 0 END) as done")
            ->groupBy('topics.subject_id')->havingRaw('SUM(CASE WHEN difficulty IS NOT NULL THEN 1 ELSE 0 END) > 0')
            ->toBase()->get();
        $subjects = Subject::with('course')->whereIn('id', $rows->pluck('sid'))->get()->keyBy('id');
        $forgot = Review::where('rating', 'forgot')->whereIn('subject_id', $rows->pluck('sid'))
            ->selectRaw('subject_id, COUNT(*) as n')->groupBy('subject_id')->pluck('n', 'subject_id');

        return $rows->map(fn ($r) => [
            'name' => $subjects[$r->sid]->name ?? '—',
            'color' => isset($subjects[$r->sid]) ? ($subjects[$r->sid]->color ?? $subjects[$r->sid]->course->color) : '#64748b',
            'easy' => (int) $r->easy, 'medium' => (int) $r->medium, 'hard' => (int) $r->hard,
            'unrated' => (int) $r->total - (int) $r->easy - (int) $r->medium - (int) $r->hard,
            'done' => (int) $r->done, 'total' => (int) $r->total, 'forgot' => (int) ($forgot[$r->sid] ?? 0),
        ])->sortByDesc('hard')->values()->all();
    }
}
