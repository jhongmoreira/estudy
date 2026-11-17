<?php

namespace App\Services;

use App\Models\StudySession;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Cache;

/**
 * Constância (docs/especificacao.md §3.5): dias seguidos com pelo menos `streak_min` minutos.
 * Dias fora de `study_days` não quebram a sequência (e contam se houve estudo). Hoje só conta
 * quando bate o mínimo — antes disso, ainda não quebra.
 */
class StreakService
{
    public static function cacheKey(int $userId): string
    {
        return "streak:{$userId}";
    }

    /** @return array{current: int, best: int, today_counts: bool} */
    public function for(User $user): array
    {
        return Cache::remember(self::cacheKey($user->id).':'.CarbonImmutable::today()->toDateString(), 3600, fn () => $this->compute($user));
    }

    /** @return array{current: int, best: int, today_counts: bool} */
    public function compute(User $user): array
    {
        $min = max(1, (int) $user->setting('streak_min')) * 60;
        $today = CarbonImmutable::today();

        /** @var array<string, int> $totals */
        $totals = StudySession::query()->where('user_id', $user->id)
            ->selectRaw('date, SUM(duration_seconds) as total')->groupBy('date')
            ->pluck('total', 'date')->map(fn ($v) => (int) $v)->all();

        if ($totals === []) {
            return ['current' => 0, 'best' => 0, 'today_counts' => false];
        }

        $counts = fn (CarbonImmutable $d) => ($totals[$d->toDateString()] ?? 0) >= $min;

        // Sequência atual: de hoje para trás
        $current = 0;
        for ($d = $today; ; $d = $d->subDay()) {
            if ($counts($d)) {
                $current++;
            } elseif ($d->isSameDay($today) || ! $user->studiesOn($d)) {
                // hoje ainda pode bater a meta; dia de folga não quebra
            } else {
                break;
            }
            if ($d->lt($today->subDays(800))) {
                break;
            }
        }

        // Recorde: varre do primeiro dia com estudo até hoje
        $best = 0;
        $run = 0;
        $first = CarbonImmutable::parse(min(array_keys($totals)));
        for ($d = $first; $d->lte($today); $d = $d->addDay()) {
            if ($counts($d)) {
                $run++;
                $best = max($best, $run);
            } elseif (! $d->isSameDay($today) && $user->studiesOn($d)) {
                $run = 0;
            }
        }

        return ['current' => $current, 'best' => max($best, $current), 'today_counts' => $counts($today)];
    }

    public static function forget(int $userId): void
    {
        Cache::forget(self::cacheKey($userId).':'.CarbonImmutable::today()->toDateString());
    }
}
