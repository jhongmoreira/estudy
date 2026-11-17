<?php

namespace App\Services;

use App\Models\AgendaItem;
use App\Models\Review;
use App\Models\User;
use App\Notifications\StudyAlert;
use App\Support\Duration;
use App\Support\Week;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Avisos do Estudy (docs/etapas/08). Roda a cada 15 min; cada aviso tem um horário e é enviado
 * dentro de uma janela de 1 hora depois dele, uma única vez por dia (notification_logs).
 */
class NotificationService
{
    /** Horários fixos dos avisos que não dependem do lembrete diário. */
    public const MORNING = '08:00';

    public const EVENING = '19:00';

    public const STREAK = '21:00';

    public function __construct(
        private SessionService $sessions,
        private StreakService $streaks,
        private SuggestionService $suggestions,
    ) {}

    public function sendOnce(User $user, string $key, StudyAlert $alert): bool
    {
        try {
            DB::table('notification_logs')->insert(['user_id' => $user->id, 'key' => $key, 'sent_at' => now()]);
        } catch (QueryException) {
            return false; // já enviado
        }

        $user->notify($alert);

        return true;
    }

    /** Rotina do cron. Retorna quantos avisos saíram. */
    public function dispatchDue(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $sent = 0;

        User::query()->whereNotNull('onboarded_at')->each(function (User $user) use ($now, &$sent) {
            Auth::setUser($user);
            try {
                $sent += $this->forUser($user, $now);
            } finally {
                Auth::forgetUser();
            }
        });

        return $sent;
    }

    public function forUser(User $user, CarbonImmutable $now): int
    {
        $today = $now->startOfDay();
        $day = $today->toDateString();
        $sent = 0;

        // Revisões de hoje (manhã)
        if ($user->wantsNotification('reviews') && $this->inWindow($now, self::MORNING)) {
            $due = Review::due()->count();
            if ($due > 0) {
                $late = Review::pending()->where('due_on', '<', $day)->count();
                $sent += (int) $this->sendOnce($user, "reviews:$day", new StudyAlert(
                    $due === 1 ? '1 revisão para hoje' : "$due revisões para hoje",
                    $late > 0 ? "$late atrasada(s). Revisar agora leva poucos minutos." : 'Retome o que estudou e fixe de vez.',
                    route('reviews', absolute: false), 'review', 'reviews',
                ));
            }
        }

        // Agenda: no dia (manhã) e na véspera (noite)
        if ($user->wantsNotification('agenda')) {
            if ($this->inWindow($now, self::MORNING)) {
                foreach (AgendaItem::with('subject')->where('status', 'open')->where('due_on', $day)->get() as $item) {
                    $sent += (int) $this->sendOnce($user, "agenda:{$item->id}:day", new StudyAlert(
                        'Hoje: '.$item->title,
                        trim(($item->subject->name ?? '').($item->due_time ? ' · às '.$item->due_time : '')) ?: $item->typeLabel(),
                        route('agenda', absolute: false), 'calendar', "agenda-{$item->id}",
                    ));
                }
            }
            if ($this->inWindow($now, self::EVENING)) {
                foreach (AgendaItem::with('subject')->where('status', 'open')->where('due_on', $today->addDay()->toDateString())->get() as $item) {
                    $sent += (int) $this->sendOnce($user, "agenda:{$item->id}:eve", new StudyAlert(
                        'Amanhã: '.$item->title,
                        ($item->subject->name ?? $item->typeLabel()).'. Que tal uma última revisada hoje?',
                        route('agenda', absolute: false), 'calendar', "agenda-{$item->id}",
                    ));
                }
            }
        }

        // Lembrete diário: só em dias de estudo e se a meta ainda não foi batida
        $reminder = $user->setting('reminder_time');
        if ($user->wantsNotification('daily') && is_string($reminder) && $this->inWindow($now, $reminder) && $user->studiesOn($today)) {
            $done = $this->sessions->dailyTotals($today, $today)[$day] ?? 0;
            $goal = $user->dailyGoalMinutes() * 60;
            if ($done < $goal) {
                $first = $this->suggestions->forToday(Week::of($today, $user->weekStartsOn()), 1)[0] ?? null;
                $due = Review::due()->count();
                $parts = [];
                if ($first) {
                    $parts[] = 'Sugestão: '.$first['title'];
                }
                if ($due > 0) {
                    $parts[] = $due === 1 ? '1 revisão pendente' : "$due revisões pendentes";
                }
                $sent += (int) $this->sendOnce($user, "daily:$day", new StudyAlert(
                    $done > 0 ? 'Faltam '.Duration::human($goal - $done).' para a meta de hoje' : 'Bora estudar?',
                    $parts !== [] ? implode(' · ', $parts).'.' : 'Que tal 25 minutos de foco agora?',
                    route('study', absolute: false), 'play', 'daily',
                ));
            }
        }

        // Sequência em risco
        if ($user->wantsNotification('streak') && $this->inWindow($now, self::STREAK)) {
            $streak = $this->streaks->compute($user);
            if ($streak['current'] >= 3 && ! $streak['today_counts'] && $user->studiesOn($today)) {
                $sent += (int) $this->sendOnce($user, "streak:$day", new StudyAlert(
                    "Sua sequência de {$streak['current']} dias acaba hoje 🔥",
                    'Bastam '.(int) $user->setting('streak_min').' minutos para mantê-la.',
                    route('study', absolute: false), 'flame', 'streak',
                ));
            }
        }

        return $sent;
    }

    /** O horário HH:MM já passou hoje, há menos de 1 hora. */
    private function inWindow(CarbonImmutable $now, string $time): bool
    {
        if (! preg_match('/^([01]\d|2[0-3]):([0-5]\d)$/', $time)) {
            return false;
        }
        $at = $now->setTimeFromTimeString($time);

        return $now->gte($at) && $now->lt($at->addHour());
    }
}
