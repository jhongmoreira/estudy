<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Support\Collection;

/**
 * Progresso de disciplinas e cursos (docs/especificacao.md §3.1).
 * - Disciplina: por minutos estimados se TODOS os tópicos tiverem estimativa; senão, por quantidade.
 * - Curso: média das disciplinas ponderada pelo nº de tópicos.
 */
class ProgressService
{
    /**
     * Estatísticas por disciplina, numa única consulta agregada.
     *
     * @param  list<int>  $subjectIds
     * @return Collection<int, array{total: int, done: int, progress: float}> chave = subject_id
     */
    public function forSubjects(array $subjectIds): Collection
    {
        if ($subjectIds === []) {
            return collect();
        }

        $rows = Topic::query()
            ->whereIn('subject_id', $subjectIds)
            ->selectRaw('subject_id')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw("SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) as done")
            ->selectRaw('SUM(CASE WHEN estimated_min IS NULL THEN 1 ELSE 0 END) as without_estimate')
            ->selectRaw('COALESCE(SUM(estimated_min), 0) as minutes')
            ->selectRaw("COALESCE(SUM(CASE WHEN status = 'done' THEN estimated_min ELSE 0 END), 0) as done_minutes")
            ->groupBy('subject_id')
            ->toBase()
            ->get()
            ->keyBy('subject_id');

        return collect($subjectIds)->mapWithKeys(function (int $id) use ($rows) {
            $row = $rows->get($id);
            $total = (int) ($row->total ?? 0);
            $done = (int) ($row->done ?? 0);

            if ($total === 0) {
                return [$id => ['total' => 0, 'done' => 0, 'progress' => 0.0]];
            }

            $useMinutes = (int) $row->without_estimate === 0 && (int) $row->minutes > 0;
            $progress = $useMinutes ? (int) $row->done_minutes / (int) $row->minutes : $done / $total;

            return [$id => ['total' => $total, 'done' => $done, 'progress' => round($progress, 4)]];
        });
    }

    /** @return array{total: int, done: int, progress: float} */
    public function forSubject(Subject $subject): array
    {
        return $this->forSubjects([$subject->id])->get($subject->id);
    }

    /**
     * @param  iterable<Course>  $courses
     * @return Collection<int, array{subjects: int, total: int, done: int, progress: float}> chave = course_id
     */
    public function forCourses(iterable $courses): Collection
    {
        $courseIds = collect($courses)->pluck('id')->all();
        $subjects = Subject::query()->whereIn('course_id', $courseIds)->get(['id', 'course_id']);
        $stats = $this->forSubjects($subjects->pluck('id')->all());

        return collect($courseIds)->mapWithKeys(function (int $courseId) use ($subjects, $stats) {
            $own = $subjects->where('course_id', $courseId);
            $total = 0;
            $done = 0;
            $weighted = 0.0;

            foreach ($own as $subject) {
                $s = $stats->get($subject->id);
                $total += $s['total'];
                $done += $s['done'];
                $weighted += $s['progress'] * $s['total'];
            }

            return [$courseId => [
                'subjects' => $own->count(),
                'total' => $total,
                'done' => $done,
                'progress' => $total > 0 ? round($weighted / $total, 4) : 0.0,
            ]];
        });
    }

    /** @return array{subjects: int, total: int, done: int, progress: float} */
    public function forCourse(Course $course): array
    {
        return $this->forCourses([$course])->get($course->id);
    }
}
