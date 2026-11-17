<?php

namespace App\Services;

use App\Models\ActiveTimer;
use App\Models\StudySession;
use App\Models\Subject;
use App\Models\Topic;
use App\Models\User;
use App\Notifications\StudyAlert;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
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
            $attributes = ['paused_at' => CarbonImmutable::now()];
            if ($timer->pomodoro) {
                // Registra até qual bloco de foco já foi: o cron não pausa/avisa de novo
                $focus = $this->user()->pomodoro()['focus'] * 60;
                $attributes['pomodoro_block'] = max($timer->pomodoro_block, intdiv($timer->elapsedSeconds(), $focus));
                $attributes['break_alerted_at'] = null;
            }
            $timer->update($attributes);
        }
    }

    public function resume(ActiveTimer $timer): void
    {
        if ($timer->paused_at !== null) {
            $timer->update([
                'paused_seconds' => $timer->paused_seconds + max(0, CarbonImmutable::now()->getTimestamp() - $timer->paused_at->getTimestamp()),
                'paused_at' => null,
                'break_alerted_at' => null,
            ]);
        }
    }

    /** O navegador já avisou o fim da pausa (o servidor não manda push repetido). */
    public function markBreakAlerted(ActiveTimer $timer): void
    {
        if ($timer->isPaused() && $timer->break_alerted_at === null) {
            $timer->update(['break_alerted_at' => CarbonImmutable::now()]);
        }
    }

    /**
     * Pomodoro pelo servidor (cron a cada minuto), para funcionar com o app fechado/celular bloqueado:
     * ao fim de cada foco, pausa no horário exato em que o foco terminou e manda push; ao fim da pausa, avisa.
     * Retorna 'pause', 'break_over' ou null.
     */
    public function pomodoroTick(ActiveTimer $timer, User $user, ?CarbonImmutable $now = null): ?string
    {
        if (! $timer->pomodoro) {
            return null;
        }
        $now ??= CarbonImmutable::now();
        $cfg = $user->pomodoro();
        $focus = $cfg['focus'] * 60;

        if (! $timer->isPaused()) {
            $elapsed = $timer->elapsedSeconds($now);
            $block = intdiv($elapsed, $focus);
            if ($block <= $timer->pomodoro_block) {
                return null;
            }
            $timer->update([
                'paused_at' => $now->subSeconds($elapsed - $block * $focus), // momento em que o foco acabou
                'pomodoro_block' => $block,
                'break_alerted_at' => null,
            ]);
            $long = $block % $cfg['every'] === 0;
            $user->notifyNow(new StudyAlert(
                'Hora da pausa! ⏸',
                "{$cfg['focus']} min de foco em {$timer->subject->name} concluídos. Pausa de ".($long ? $cfg['long'] : $cfg['short']).' min.',
                route('focus', absolute: false), 'clock', 'estudy-timer',
            ));

            return 'pause';
        }

        if ($timer->pomodoro_block > 0 && $timer->break_alerted_at === null && $timer->paused_at !== null) {
            $breakLen = ($timer->pomodoro_block % $cfg['every'] === 0 ? $cfg['long'] : $cfg['short']) * 60;
            if ($now->getTimestamp() - $timer->paused_at->getTimestamp() >= $breakLen) {
                $timer->update(['break_alerted_at' => $now]);
                $user->notifyNow(new StudyAlert(
                    'Pausa encerrada ▶',
                    "Hora de voltar para {$timer->subject->name}.",
                    route('focus', absolute: false), 'play', 'estudy-timer',
                ));

                return 'break_over';
            }
        }

        return null;
    }

    /** Roda o Pomodoro de todos os usuários (comando timers:pomodoro). */
    public function pomodoroTickAll(): int
    {
        $count = 0;
        $previous = Auth::user();
        ActiveTimer::withoutGlobalScopes()->where('pomodoro', true)->with('user')->get()->each(function (ActiveTimer $timer) use (&$count) {
            Auth::setUser($timer->user);
            $timer->load('subject');
            $count += $this->pomodoroTick($timer, $timer->user) !== null ? 1 : 0;
        });
        $previous !== null ? Auth::setUser($previous) : Auth::forgetUser();

        return $count;
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
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
