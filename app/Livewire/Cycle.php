<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\CycleItem;
use App\Models\StudyCycle;
use App\Models\Subject;
use App\Services\CycleService;
use App\Support\Reorder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Ciclo de estudos')]
class Cycle extends Component
{
    public bool $editing = false;

    // Assistente
    public string $name = 'Meu ciclo';

    /** @var array<int|string, int> subject_id => peso (0 = fora) */
    public array $weights = [];

    public int $totalHours = 10;

    public int $maxBlock = 60;

    // Novo item manual
    public ?int $newSubjectId = null;

    public int $newMinutes = 60;

    public function openBuilder(): void
    {
        $cycle = StudyCycle::first();
        $this->name = $cycle->name ?? 'Meu ciclo';
        $current = $cycle ? $cycle->items()->get()->groupBy('subject_id')->map->sum('target_min') : collect();
        $this->weights = [];
        foreach ($this->subjects() as $subject) {
            $this->weights[$subject->id] = $current->has($subject->id) ? 3 : 0;
        }
        if ($current->isNotEmpty()) {
            $this->totalHours = max(1, (int) round($current->sum() / 60));
        }
        $this->dispatch('open-sheet', 'cycle-builder');
    }

    public function build(CycleService $cycles): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:80'],
            'totalHours' => ['required', 'integer', 'min:1', 'max:100'],
            'maxBlock' => ['required', 'integer', 'min:15', 'max:240'],
            'weights.*' => ['integer', 'between:0,5'],
        ], attributes: ['totalHours' => 'horas por volta', 'maxBlock' => 'bloco máximo']);

        $items = $cycles->distribute(array_map('intval', $this->weights), $this->totalHours * 60, $this->maxBlock);
        if ($items === []) {
            $this->addError('weights', 'Escolha pelo menos uma disciplina (peso maior que zero).');

            return;
        }

        $cycle = StudyCycle::firstOrCreate([], ['name' => $this->name]);
        $cycle->update(['name' => $this->name]);
        $cycles->replaceItems($cycle, $items);
        $cycles->activate($cycle);

        $this->dispatch('close-sheet', 'cycle-builder');
        $this->dispatch('toast', message: 'Ciclo montado com '.count($items).' blocos.');
    }

    public function addItem(): void
    {
        $this->validate(['newSubjectId' => ['required', 'integer'], 'newMinutes' => ['required', 'integer', 'min:5', 'max:600']],
            ['newSubjectId.required' => 'Escolha a disciplina.'], ['newMinutes' => 'minutos']);
        Subject::findOrFail($this->newSubjectId);
        $cycle = StudyCycle::firstOrCreate([], ['name' => 'Meu ciclo']);
        CycleItem::create([
            'study_cycle_id' => $cycle->id,
            'subject_id' => $this->newSubjectId,
            'target_min' => $this->newMinutes,
            'position' => (int) $cycle->items()->max('position') + 1,
        ]);
        if (! StudyCycle::where('is_active', true)->exists()) {
            app(CycleService::class)->activate($cycle);
        }
        $this->newSubjectId = null;
    }

    public function updateMinutes(int $id, int $minutes): void
    {
        CycleItem::findOrFail($id)->update(['target_min' => max(5, min(600, $minutes))]);
    }

    public function removeItem(int $id): void
    {
        CycleItem::findOrFail($id)->delete();
    }

    public function move(int $id, int $direction): void
    {
        $item = CycleItem::findOrFail($id);
        Reorder::move(CycleItem::query()->where('study_cycle_id', $item->study_cycle_id)->orderBy('position')->orderBy('id'), $id, $direction);
    }

    public function skip(int $id, CycleService $cycles): void
    {
        $cycles->skip(CycleItem::findOrFail($id));
        $this->dispatch('toast', message: 'Bloco pulado.');
    }

    public function restart(CycleService $cycles): void
    {
        $cycles->restartRound(StudyCycle::firstOrFail());
        $this->dispatch('toast', message: 'Volta reiniciada.');
    }

    public function toggleActive(CycleService $cycles): void
    {
        $cycle = StudyCycle::firstOrFail();
        if ($cycle->is_active) {
            $cycle->update(['is_active' => false]);
            $this->dispatch('toast', message: 'Ciclo pausado: as sessões não contam até reativar.');
        } else {
            $cycles->activate($cycle);
            $this->dispatch('toast', message: 'Ciclo ativado.');
        }
    }

    #[On('session-saved')]
    public function refreshCycle(): void {}

    /** @return Collection<int, Subject> */
    private function subjects(): Collection
    {
        return Subject::with('course')->where('status', 'active')
            ->whereHas('course', fn ($q) => $q->where('status', 'active'))
            ->orderBy('course_id')->orderBy('position')->get();
    }

    public function render(CycleService $cycles): View
    {
        $cycle = StudyCycle::with('items.subject.course')->first();
        $items = $cycle !== null ? $cycle->items : collect();
        $preview = $cycles->distribute(array_map('intval', $this->weights), $this->totalHours * 60, $this->maxBlock);

        return view('livewire.cycle', [
            'cycle' => $cycle,
            'items' => $items,
            'current' => $cycle?->is_active ? $cycle->currentItem() : null,
            'roundDone' => $items->sum(fn (CycleItem $i) => min($i->done_seconds, $i->target_min * 60)),
            'roundTotal' => $items->sum(fn (CycleItem $i) => $i->target_min * 60),
            'subjects' => $this->subjects(),
            'courses' => Course::where('status', 'active')->ordered()->get(['id', 'name', 'color']),
            'preview' => $preview,
            'subjectNames' => $this->subjects()->pluck('name', 'id'),
        ]);
    }
}
