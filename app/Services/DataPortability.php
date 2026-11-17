<?php

namespace App\Services;

use App\Models\ActiveTimer;
use App\Models\AgendaItem;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\CycleItem;
use App\Models\Review;
use App\Models\StudyCycle;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Exportar/importar todos os dados do usuário em JSON (backup e migração entre contas)
 * e "zerar dados". Os ids viram referências internas do arquivo e são remapeados na importação.
 */
class DataPortability
{
    public const FORMAT = 1;

    /** @return array<string, mixed> */
    public function export(User $user): array
    {
        $courses = Course::where('user_id', $user->id)->orderBy('id')->get();
        $subjects = Subject::where('user_id', $user->id)->orderBy('id')->get();
        $cycles = StudyCycle::where('user_id', $user->id)->orderBy('id')->get();

        $pick = fn ($model, array $keys) => Arr::only($model->getAttributes(), $keys);

        return [
            'app' => 'Estudy',
            'format' => self::FORMAT,
            'exported_at' => now()->toIso8601String(),
            'settings' => $user->settings ?? [],
            'courses' => $courses->map(fn (Course $c) => $pick($c, ['id', 'name', 'type', 'institution', 'color', 'emoji', 'status', 'started_on', 'target_on', 'completed_on', 'workload_hours', 'weekly_goal_min', 'url', 'notes', 'position']))->all(),
            'subjects' => $subjects->map(fn (Subject $s) => $pick($s, ['id', 'course_id', 'name', 'color', 'term', 'status', 'weekly_goal_min', 'passing_grade', 'notes', 'position']))->all(),
            'topics' => Topic::where('user_id', $user->id)->orderBy('id')->get()->map(fn (Topic $t) => $pick($t, ['id', 'subject_id', 'title', 'status', 'difficulty', 'estimated_min', 'resource_url', 'notes', 'position', 'done_on']))->all(),
            'sessions' => StudySession::where('user_id', $user->id)->orderBy('id')->get()->map(fn (StudySession $s) => $pick($s, ['id', 'subject_id', 'topic_id', 'date', 'started_at', 'duration_seconds', 'kind', 'pages', 'questions_total', 'questions_correct', 'source', 'notes']))->all(),
            'reviews' => Review::where('user_id', $user->id)->orderBy('id')->get()->map(fn (Review $r) => $pick($r, ['topic_id', 'step', 'due_on', 'status', 'rating', 'done_on']))->all(),
            'agenda' => AgendaItem::where('user_id', $user->id)->orderBy('id')->get()->map(fn (AgendaItem $a) => $pick($a, ['course_id', 'subject_id', 'title', 'type', 'due_on', 'due_time', 'status', 'grade', 'max_grade', 'weight', 'notes']))->all(),
            'cycles' => $cycles->map(fn (StudyCycle $c) => $pick($c, ['id', 'name', 'is_active', 'round']) + [
                'items' => CycleItem::where('study_cycle_id', $c->id)->orderBy('position')->get()->map(fn (CycleItem $i) => $pick($i, ['subject_id', 'target_min', 'done_seconds', 'position']))->all(),
            ])->all(),
        ];
    }

    /**
     * Contagens do arquivo para a prévia.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, int>
     */
    public function summarize(array $data): array
    {
        $this->assertValid($data);

        return [
            'cursos' => count($data['courses']),
            'disciplinas' => count($data['subjects']),
            'tópicos' => count($data['topics']),
            'sessões' => count($data['sessions']),
            'revisões' => count($data['reviews'] ?? []),
            'itens da agenda' => count($data['agenda'] ?? []),
        ];
    }

    /**
     * Substitui todos os dados do usuário pelos do arquivo.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, int>
     */
    public function import(User $user, array $data): array
    {
        $this->assertValid($data);

        return DB::transaction(function () use ($user, $data) {
            $this->reset($user);
            $courseMap = $subjectMap = $topicMap = [];
            $owned = fn (array $attrs) => $attrs + ['user_id' => $user->id];

            foreach ($data['courses'] as $c) {
                $courseMap[$c['id']] = Course::forceCreate($owned(Arr::except($c, ['id'])))->id;
            }
            foreach ($data['subjects'] as $s) {
                if (! isset($courseMap[$s['course_id']])) {
                    continue;
                }
                $subjectMap[$s['id']] = Subject::forceCreate($owned(['course_id' => $courseMap[$s['course_id']]] + Arr::except($s, ['id', 'course_id'])))->id;
            }
            foreach ($data['topics'] as $t) {
                if (! isset($subjectMap[$t['subject_id']])) {
                    continue;
                }
                $topicMap[$t['id']] = Topic::forceCreate($owned(['subject_id' => $subjectMap[$t['subject_id']]] + Arr::except($t, ['id', 'subject_id'])))->id;
            }
            $sessions = 0;
            foreach ($data['sessions'] as $s) {
                if (! isset($subjectMap[$s['subject_id']])) {
                    continue;
                }
                $subjectId = $subjectMap[$s['subject_id']];
                StudySession::withoutEvents(fn () => StudySession::forceCreate($owned([
                    'course_id' => Subject::withoutGlobalScopes()->whereKey($subjectId)->value('course_id'),
                    'subject_id' => $subjectId,
                    'topic_id' => $topicMap[$s['topic_id'] ?? 0] ?? null,
                ] + Arr::except($s, ['id', 'subject_id', 'topic_id']))));
                $sessions++;
            }
            foreach ($data['reviews'] ?? [] as $r) {
                if (! isset($topicMap[$r['topic_id']])) {
                    continue;
                }
                $topicId = $topicMap[$r['topic_id']];
                Review::forceCreate($owned([
                    'topic_id' => $topicId,
                    'subject_id' => Topic::withoutGlobalScopes()->whereKey($topicId)->value('subject_id'),
                ] + Arr::except($r, ['topic_id'])));
            }
            foreach ($data['agenda'] ?? [] as $a) {
                if (! isset($courseMap[$a['course_id']])) {
                    continue;
                }
                AgendaItem::forceCreate($owned([
                    'course_id' => $courseMap[$a['course_id']],
                    'subject_id' => $subjectMap[$a['subject_id'] ?? 0] ?? null,
                ] + Arr::except($a, ['course_id', 'subject_id'])));
            }
            foreach ($data['cycles'] ?? [] as $c) {
                $cycle = StudyCycle::forceCreate($owned(Arr::only($c, ['name', 'is_active', 'round'])));
                foreach ($c['items'] ?? [] as $i) {
                    if (isset($subjectMap[$i['subject_id']])) {
                        CycleItem::forceCreate($owned(['study_cycle_id' => $cycle->id, 'subject_id' => $subjectMap[$i['subject_id']]] + Arr::except($i, ['subject_id'])));
                    }
                }
            }

            if (is_array($data['settings'] ?? null)) {
                $user->update(['settings' => $data['settings']]);
            }
            StreakService::forget($user->id);

            return ['cursos' => count($courseMap), 'disciplinas' => count($subjectMap), 'tópicos' => count($topicMap), 'sessões' => $sessions];
        });
    }

    /** Apaga todos os dados de estudo (mantém login e preferências). */
    public function reset(User $user): void
    {
        DB::transaction(function () use ($user) {
            Certificate::where('user_id', $user->id)->get()->each->delete(); // apaga os arquivos também
            ActiveTimer::where('user_id', $user->id)->delete();
            StudyCycle::where('user_id', $user->id)->delete();
            Course::where('user_id', $user->id)->get()->each->delete(); // cascata: disciplinas, tópicos, sessões, revisões, agenda
            DB::table('notification_logs')->where('user_id', $user->id)->delete();
        });
        StreakService::forget($user->id);
    }

    /** @param  array<string, mixed>  $data */
    private function assertValid(array $data): void
    {
        $ok = ($data['app'] ?? null) === 'Estudy'
            && ($data['format'] ?? null) === self::FORMAT
            && is_array($data['courses'] ?? null) && is_array($data['subjects'] ?? null)
            && is_array($data['topics'] ?? null) && is_array($data['sessions'] ?? null);

        if (! $ok) {
            throw ValidationException::withMessages(['backup' => 'Este arquivo não é um backup do Estudy (ou é de uma versão incompatível).']);
        }
    }
}
