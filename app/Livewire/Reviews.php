<?php

namespace App\Livewire;

use App\Models\Review;
use App\Services\ReviewService;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Revisões')]
class Reviews extends Component
{
    public ?int $reviewId = null;

    public string $moveDate = '';

    public bool $showFuture = false;

    /** Abre a folha de avaliação ("Como foi a revisão?"). */
    public function rate(int $id): void
    {
        $this->reviewId = Review::pending()->findOrFail($id)->id;
        $this->dispatch('open-sheet', 'rate-review');
    }

    public function complete(string $rating, ReviewService $reviews): void
    {
        if (! array_key_exists($rating, Review::RATINGS)) {
            return;
        }
        $review = Review::pending()->findOrFail($this->reviewId);
        $next = $reviews->complete($review, $rating);

        $this->dispatch('close-sheet', 'rate-review');
        $this->dispatch('toast', message: $next
            ? 'Feito! Próxima revisão em '.$next->due_on->translatedFormat('j \d\e M').'.'
            : 'Tópico consolidado! Ciclo de revisões concluído. 🎉');
        $this->reviewId = null;
    }

    public function skip(int $id, ReviewService $reviews): void
    {
        $review = Review::pending()->findOrFail($id);
        $reviews->skip($review);
        $this->dispatch('toast', message: 'Revisão adiada para '.$review->refresh()->due_on->translatedFormat('j \d\e M').'.');
    }

    public function openMove(int $id): void
    {
        $review = Review::pending()->findOrFail($id);
        $this->reviewId = $review->id;
        $this->moveDate = $review->due_on->toDateString();
        $this->dispatch('open-sheet', 'move-review');
    }

    public function move(ReviewService $reviews): void
    {
        $this->validate(['moveDate' => ['required', 'date', 'after_or_equal:today']], ['moveDate.after_or_equal' => 'Escolha hoje ou uma data futura.'], ['moveDate' => 'data']);
        $reviews->moveTo(Review::pending()->findOrFail($this->reviewId), CarbonImmutable::parse($this->moveDate));
        $this->dispatch('close-sheet', 'move-review');
        $this->dispatch('toast', message: 'Revisão remarcada.');
    }

    public function rescheduleOverdue(ReviewService $reviews): void
    {
        $count = $reviews->rescheduleOverdue();
        $this->dispatch('toast', message: $count === 1 ? '1 revisão reagendada.' : "{$count} revisões reagendadas.");
    }

    #[On('session-saved')]
    public function refreshList(): void {}

    public function render(): View
    {
        $today = CarbonImmutable::today();
        $pending = Review::pending()->with(['topic', 'subject.course'])->orderBy('due_on')->orderBy('id')->get();
        $total = count(auth()->user()?->reviewIntervals() ?? [1, 7, 30]);

        return view('livewire.reviews', [
            'overdue' => $pending->filter(fn (Review $r) => $r->due_on->lt($today)),
            'today' => $pending->filter(fn (Review $r) => $r->due_on->isSameDay($today)),
            'week' => $pending->filter(fn (Review $r) => $r->due_on->gt($today) && $r->due_on->lte($today->addDays(7))),
            'later' => $pending->filter(fn (Review $r) => $r->due_on->gt($today->addDays(7))),
            'steps' => $total,
            'doneThisWeek' => Review::where('status', 'done')->where('done_on', '>=', $today->subDays(6)->toDateString())->count(),
            'selected' => $this->reviewId ? $pending->firstWhere('id', $this->reviewId) : null,
        ]);
    }
}
