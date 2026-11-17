<?php

namespace App\Livewire;

use App\Models\AgendaItem;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Agenda')]
class Agenda extends Component
{
    #[Url(as: 'ver', except: 'lista')]
    public string $view = 'lista';

    #[Url(as: 'mes', except: '')]
    public string $month = '';

    public function toggleDone(int $id): void
    {
        $item = AgendaItem::findOrFail($id);
        $item->update(['status' => $item->isDone() ? 'open' : 'done']);
    }

    public function setView(string $view): void
    {
        $this->view = $view === 'mes' ? 'mes' : 'lista';
    }

    public function shiftMonth(int $delta): void
    {
        $this->month = $this->monthStart()->addMonths($delta)->format('Y-m');
    }

    private function monthStart(): CarbonImmutable
    {
        return preg_match('/^\d{4}-\d{2}$/', $this->month)
            ? CarbonImmutable::createFromFormat('!Y-m', $this->month)
            : CarbonImmutable::today()->startOfMonth();
    }

    #[On('agenda-saved')]
    public function refreshAgenda(): void {}

    public function render(): View
    {
        $today = CarbonImmutable::today();
        $weekEnd = $today->addDays(7);

        $open = AgendaItem::with(['course', 'subject'])->where('status', 'open')->orderBy('due_on')->orderBy('due_time')->get();
        $month = $this->monthStart();
        $gridStart = $month->subDays(((int) $month->format('N')) - 1); // segunda
        $monthItems = AgendaItem::with(['course', 'subject'])
            ->whereBetween('due_on', [$gridStart->toDateString(), $gridStart->addDays(41)->toDateString()])
            ->get()->groupBy(fn (AgendaItem $i) => $i->due_on->toDateString());

        return view('livewire.agenda', [
            'late' => $open->filter(fn (AgendaItem $i) => $i->due_on->lt($today)),
            'week' => $open->filter(fn (AgendaItem $i) => $i->due_on->gte($today) && $i->due_on->lte($weekEnd)),
            'later' => $open->filter(fn (AgendaItem $i) => $i->due_on->gt($weekEnd)),
            'done' => AgendaItem::with(['course', 'subject'])->where('status', 'done')->orderByDesc('due_on')->limit(15)->get(),
            'monthStart' => $month,
            'gridStart' => $gridStart,
            'monthItems' => $monthItems,
        ]);
    }
}
