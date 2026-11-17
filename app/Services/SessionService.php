<?php

namespace App\Services;

use App\Events\SessionRecorded;
use App\Models\ActiveTimer;
use App\Models\Review;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\Topic;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

/** Gravação de sessões de estudo, pelo cronômetro ou manual (docs/especificacao.md §3.2–3.3). */
class SessionService
{
    public function __construct(private ReviewService $reviews) {}

    /**
     * @param  array<string, mixed>  $input  subject_id, topic_id?, date, started_at?, duration_seconds, kind,
     *                                       pages?, questions_total?, questions_correct?, notes?, mark_done?,
     *                                       review_rating? (good|hard|forgot — ao revisar um tópico com revisão pendente)
     */
    public function record(array $input, ?ActiveTimer $fromTimer = null, ?StudySession $existing = null): StudySession
    {
        $data = $this->validate($input);
        $subject = Subject::findOrFail($data['subject_id']);
        $topic = isset($data['topic_id']) ? Topic::where('subject_id', $subject->id)->findOrFail($data['topic_id']) : null;

        return DB::transaction(function () use ($data, $subject, $topic, $fromTimer, $existing) {
            $attributes = [
                'course_id' => $subject->course_id,
                'subject_id' => $subject->id,
                'topic_id' => $topic?->id,
                'date' => $data['date'],
                'started_at' => $data['started_at'] ?? null,
                'duration_seconds' => $data['duration_seconds'],
                'kind' => $data['kind'],
                'pages' => $data['pages'] ?? null,
                'questions_total' => $data['questions_total'] ?? null,
                'questions_correct' => isset($data['questions_total']) ? ($data['questions_correct'] ?? 0) : null,
                'notes' => ($data['notes'] ?? '') !== '' ? $data['notes'] : null,
            ];

            if ($existing !== null) {
                $existing->update($attributes);
                $session = $existing;
            } else {
                $session = StudySession::create($attributes + ['source' => $fromTimer ? 'timer' : 'manual']);
            }

            if ($topic !== null) {
                if (! empty($data['mark_done'])) {
                    $topic->update(['status' => 'done', 'done_on' => $topic->done_on ?? CarbonImmutable::today()]);
                } elseif ($topic->status === 'todo') {
                    $topic->update(['status' => 'doing']);
                }
            }

            $fromTimer?->delete();

            if ($existing === null) {
                // Revisões: estudar agenda a 1ª; uma sessão de revisão conclui a pendente
                if ($session->kind === 'review' && $topic !== null) {
                    $pending = Review::pending()->where('topic_id', $topic->id)->first();
                    if ($pending !== null) {
                        $this->reviews->complete($pending, (string) ($data['review_rating'] ?? 'good'), $session);
                    }
                } else {
                    $this->reviews->scheduleAfterStudy($session);
                }

                SessionRecorded::dispatch($session);
            }

            return $session;
        });
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    private function validate(array $input): array
    {
        $input = array_map(fn ($v) => $v === '' ? null : $v, $input);

        return Validator::make($input, [
            'subject_id' => ['required', 'integer'],
            'topic_id' => ['nullable', 'integer'],
            'date' => ['required', 'date', 'before_or_equal:today'],
            'started_at' => ['nullable', 'date'],
            'duration_seconds' => ['required', 'integer', 'min:'.StudySession::MIN_SECONDS, 'max:'.StudySession::MAX_SECONDS],
            'kind' => ['required', Rule::in(array_keys(StudySession::KINDS))],
            'pages' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'questions_total' => ['nullable', 'integer', 'min:0', 'max:5000'],
            'questions_correct' => ['nullable', 'integer', 'min:0', 'lte:questions_total'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'mark_done' => ['nullable', 'boolean'],
            'review_rating' => ['nullable', Rule::in(array_keys(Review::RATINGS))],
        ], [
            'duration_seconds.min' => 'A sessão precisa ter pelo menos 1 minuto.',
            'duration_seconds.max' => 'A sessão pode ter no máximo 12 horas.',
            'date.before_or_equal' => 'A data não pode ser no futuro.',
            'questions_correct.lte' => 'Acertos não podem passar do total de questões.',
        ], [
            'subject_id' => 'disciplina', 'date' => 'data', 'duration_seconds' => 'duração', 'kind' => 'tipo',
            'pages' => 'páginas', 'questions_total' => 'questões', 'questions_correct' => 'acertos',
        ])->validate();
    }

    /**
     * Soma de segundos estudados por dia no intervalo (chave AAAA-MM-DD).
     *
     * @return array<string, int>
     */
    public function dailyTotals(CarbonImmutable $from, CarbonImmutable $to): array
    {
        return StudySession::query()
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('date, SUM(duration_seconds) as total')
            ->groupBy('date')
            ->pluck('total', 'date')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
