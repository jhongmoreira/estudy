<?php

namespace App\Services;

use App\Models\Review;
use App\Models\StudySession;
use App\Models\Topic;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/** Revisões espaçadas (docs/especificacao.md §3.4). */
class ReviewService
{
    /** Agenda o 1º passo depois de estudar um tópico (se ainda não houver revisão pendente). */
    public function scheduleAfterStudy(StudySession $session): ?Review
    {
        $user = $this->user();
        if ($session->topic_id === null || $session->kind === 'review' || ! $user->setting('reviews_auto')) {
            return null;
        }
        if (Review::pending()->where('topic_id', $session->topic_id)->exists()) {
            return null;
        }

        return Review::create([
            'topic_id' => $session->topic_id,
            'subject_id' => $session->subject_id,
            'origin_session_id' => $session->id,
            'step' => 1,
            'due_on' => $this->nextStudyDay($session->date->addDays($this->intervals()[0])),
        ]);
    }

    /**
     * Conclui a revisão com a avaliação e agenda a próxima:
     * good → próximo passo; hard → repete o intervalo; forgot → volta ao passo 1.
     */
    public function complete(Review $review, string $rating, ?StudySession $session = null): ?Review
    {
        $rating = array_key_exists($rating, Review::RATINGS) ? $rating : 'good';
        $intervals = $this->intervals();
        $today = CarbonImmutable::today();

        return DB::transaction(function () use ($review, $rating, $session, $intervals, $today) {
            $review->update(['status' => 'done', 'rating' => $rating, 'done_on' => $today, 'session_id' => $session?->id]);

            $step = match ($rating) {
                'good' => $review->step + 1,
                'hard' => $review->step,
                default => 1,
            };
            if ($step > count($intervals)) {
                return null; // cadeia concluída: tópico consolidado
            }

            return Review::create([
                'topic_id' => $review->topic_id,
                'subject_id' => $review->subject_id,
                'origin_session_id' => $review->origin_session_id,
                'step' => $step,
                'due_on' => $this->nextStudyDay($today->addDays($intervals[$step - 1])),
            ]);
        });
    }

    /** "Pular": a mesma revisão vai para o próximo dia de estudo. */
    public function skip(Review $review): void
    {
        $review->update(['due_on' => $this->nextStudyDay(CarbonImmutable::today()->addDay())]);
    }

    public function moveTo(Review $review, CarbonImmutable $date): void
    {
        $review->update(['due_on' => $date->max(CarbonImmutable::today())]);
    }

    /** Agenda (ou antecipa) manualmente a revisão de um tópico. */
    public function scheduleManually(Topic $topic, CarbonImmutable $date): Review
    {
        $date = $date->max(CarbonImmutable::today());
        $pending = Review::pending()->where('topic_id', $topic->id)->first();
        if ($pending) {
            $pending->update(['due_on' => $date]);

            return $pending;
        }

        return Review::create(['topic_id' => $topic->id, 'subject_id' => $topic->subject_id, 'step' => 1, 'due_on' => $date]);
    }

    /** Espalha as atrasadas a partir de hoje, no máximo N por dia (contando as que já vencem no dia). */
    public function rescheduleOverdue(): int
    {
        $perDay = max(1, (int) $this->user()->setting('reviews_per_day'));
        $today = CarbonImmutable::today();
        $overdue = Review::pending()->where('due_on', '<', $today->toDateString())->orderBy('due_on')->orderBy('id')->get();

        $day = $this->nextStudyDay($today);
        $load = Review::pending()->where('due_on', $day->toDateString())->count();

        foreach ($overdue as $review) {
            while ($load >= $perDay) {
                $day = $this->nextStudyDay($day->addDay());
                $load = Review::pending()->where('due_on', $day->toDateString())->count();
            }
            $review->update(['due_on' => $day]);
            $load++;
        }

        return $overdue->count();
    }

    /** A própria data, se for dia de estudo; senão, o próximo dia de estudo. */
    public function nextStudyDay(CarbonImmutable $date): CarbonImmutable
    {
        $user = $this->user();
        for ($i = 0; $i < 7 && ! $user->studiesOn($date); $i++) {
            $date = $date->addDay();
        }

        return $date->startOfDay();
    }

    /** @return list<int> */
    public function intervals(): array
    {
        return $this->user()->reviewIntervals();
    }

    private function user(): User
    {
        /** @var User */
        return Auth::user();
    }
}
