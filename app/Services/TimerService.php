<?php

namespace App\Services;

use App\Models\ActiveTimer;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\Topic;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

/** Cronômetro único por usuário, com estado no servidor (docs/especificacao.md §3.2). */
class TimerService
{
    /** Acima disso, a folha "Como foi?" pergunta se o cronômetro ficou ligado sem querer. */
    public const FORGOTTEN_AFTER = 4 * 3600;

    public function current(): ?ActiveTimer
    {
        return ActiveTimer::with(['subject.course', 'topic'])->first();
    }

    public function start(Subject $subject, ?Topic $topic, string $kind, bool $pomodoro = false): ActiveTimer
    {
        if (ActiveTimer::query()->exists()) {
            throw ValidationException::withMessages(['timer' => 'Já existe um cronômetro ligado. Encerre-o antes de começar outro.']);
        }
        if ($topic !== null && $topic->subject_id !== $subject->id) {
            throw ValidationException::withMessages(['topic' => 'O tópico não pertence a esta disciplina.']);
        }

        // Começar a estudar um tópico novo muda o status para "estudando"
        if ($topic !== null && $topic->status === 'todo') {
            $topic->update(['status' => 'doing']);
        }

        return ActiveTimer::create([
            'subject_id' => $subject->id,
            'topic_id' => $topic?->id,
            'kind' => array_key_exists($kind, StudySession::KINDS) ? $kind : 'theory',
            'pomodoro' => $pomodoro,
            'started_at' => CarbonImmutable::now(),
        ]);
    }

    public function pause(ActiveTimer $timer): void
    {
        if (! $timer->isPaused()) {
            $timer->update(['paused_at' => CarbonImmutable::now()]);
        }
    }

    public function resume(ActiveTimer $timer): void
    {
        if ($timer->paused_at !== null) {
            $timer->update([
                'paused_seconds' => $timer->paused_seconds + max(0, CarbonImmutable::now()->getTimestamp() - $timer->paused_at->getTimestamp()),
                'paused_at' => null,
            ]);
        }
    }

    /**
     * Congela o tempo (pausa) e devolve os dados para a folha "Como foi?".
     *
     * @return array{seconds: int, forgotten: bool}
     */
    public function freeze(ActiveTimer $timer): array
    {
        $this->pause($timer);
        $timer->refresh();
        $elapsed = $timer->elapsedSeconds();

        return [
            'seconds' => min($elapsed, StudySession::MAX_SECONDS),
            'forgotten' => $elapsed >= self::FORGOTTEN_AFTER,
        ];
    }

    public function discard(ActiveTimer $timer): void
    {
        $timer->delete();
    }
}
