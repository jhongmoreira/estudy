<?php

namespace App\Services;

use App\Models\Course;
use App\Models\StudySession;
use App\Models\Subject;
use App\Support\Week;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

/** Metas semanais por curso/disciplina (docs/especificacao.md §3.5). */
class GoalService
{
    /** @return array<int, int> course_id => segundos na semana */
    public function weekByCourse(Week $week): array
    {
        return $this->sumBy('course_id', $week);
    }

    /** @return array<int, int> subject_id => segundos na semana */
    public function weekBySubject(Week $week): array
    {
        return $this->sumBy('subject_id', $week);
    }

    /**
     * Quanto falta, proporcionalmente ao dia da semana, para cada meta semanal (só as atrasadas).
     * Metas de disciplina têm prioridade; metas de curso sugerem a disciplina menos estudada na semana.
     *
     * @return Collection<int, array{subject: Subject, deficit: int, done: int, goal: int}>
     */
    public function deficits(Week $week, CarbonImmutable $today): Collection
    {
        $elapsedDays = min(7, max(1, (int) $week->start->diffInDays($today) + 1));
        $bySubject = $this->weekBySubject($week);
        $byCourse = $this->weekByCourse($week);
        $result = collect();

        $subjects = Subject::with('course')->where('status', 'active')
            ->whereHas('course', fn ($q) => $q->where('status', 'active'))->get();

        foreach ($subjects->whereNotNull('weekly_goal_min') as $subject) {
            $goal = (int) $subject->weekly_goal_min * 60;
            $done = $bySubject[$subject->id] ?? 0;
            $deficit = (int) round($goal * $elapsedDays / 7) - $done;
            if ($deficit > 0) {
                $result->push(['subject' => $subject, 'deficit' => $deficit, 'done' => $done, 'goal' => $goal]);
            }
        }

        $courses = Course::where('status', 'active')->whereNotNull('weekly_goal_min')->get();
        foreach ($courses as $course) {
            $goal = (int) $course->weekly_goal_min * 60;
            $done = $byCourse[$course->id] ?? 0;
            $deficit = (int) round($goal * $elapsedDays / 7) - $done;
            $candidates = $subjects->where('course_id', $course->id)->whereNull('weekly_goal_min');
            if ($deficit > 0 && $candidates->isNotEmpty()) {
                $subject = $candidates->sortBy(fn (Subject $s) => $bySubject[$s->id] ?? 0)->first();
                $result->push(['subject' => $subject, 'deficit' => $deficit, 'done' => $done, 'goal' => $goal]);
            }
        }

        return $result->sortByDesc('deficit')->values();
    }

    /** @return array<int, int> */
    private function sumBy(string $column, Week $week): array
    {
        return StudySession::query()
            ->whereBetween('date', [$week->start->toDateString(), $week->end->toDateString()])
            ->selectRaw("{$column} as k, SUM(duration_seconds) as total")
            ->groupBy($column)
            ->pluck('total', 'k')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
