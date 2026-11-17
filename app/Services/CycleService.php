<?php

namespace App\Services;

use App\Models\CycleItem;
use App\Models\StudyCycle;
use App\Models\StudySession;
use App\Models\Subject;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/** Ciclo de estudos (docs/especificacao.md §3.7). */
class CycleService
{
    public function active(): ?StudyCycle
    {
        return StudyCycle::with('items.subject.course')->where('is_active', true)->first();
    }

    /**
     * Soma a sessão no primeiro item incompleto da mesma disciplina, na volta atual.
     * O que passar do alvo segue para o próximo item incompleto da mesma disciplina.
     * Quando todos completam, começa uma nova volta.
     */
    public function apply(StudySession $session): void
    {
        $cycle = $this->active();
        if ($cycle === null) {
            return;
        }

        DB::transaction(function () use ($cycle, $session) {
            $left = $session->duration_seconds;
            foreach ($cycle->items->where('subject_id', $session->subject_id) as $item) {
                if ($left <= 0) {
                    break;
                }
                if ($item->isComplete()) {
                    continue;
                }
                $add = min($left, $item->remainingSeconds());
                $item->update(['done_seconds' => $item->done_seconds + $add]);
                $left -= $add;
            }
            // Sobra (todos os blocos da disciplina já completos): fica no último bloco dela
            if ($left > 0 && ($last = $cycle->items->where('subject_id', $session->subject_id)->last()) !== null) {
                $last->update(['done_seconds' => $last->done_seconds + $left]);
            }

            $this->closeRoundIfDone($cycle);
        });
    }

    public function skip(CycleItem $item): void
    {
        $item->update(['done_seconds' => max($item->done_seconds, $item->target_min * 60)]);
        $cycle = StudyCycle::with('items')->findOrFail($item->study_cycle_id);
        $this->closeRoundIfDone($cycle);
    }

    public function restartRound(StudyCycle $cycle): void
    {
        $cycle->items()->update(['done_seconds' => 0]);
        $cycle->update(['round_started_at' => CarbonImmutable::now()]);
    }

    public function activate(StudyCycle $cycle): void
    {
        DB::transaction(function () use ($cycle) {
            StudyCycle::query()->whereKeyNot($cycle->id)->update(['is_active' => false]);
            $cycle->update(['is_active' => true, 'round_started_at' => $cycle->round_started_at ?? CarbonImmutable::now()]);
        });
    }

    /**
     * Distribui um total de minutos por volta entre disciplinas, proporcional aos pesos (1–5),
     * arredondando para múltiplos de 5 e dividindo em blocos de no máximo $maxBlock minutos,
     * intercalados (A B C A B ...) para alternar as matérias.
     *
     * @param  array<int, int>  $weights  subject_id => peso
     * @return list<array{subject_id: int, target_min: int}>
     */
    public function distribute(array $weights, int $totalMinutes, int $maxBlock = 60): array
    {
        $weights = array_filter($weights, fn ($w) => $w > 0);
        $sum = array_sum($weights);
        if ($sum === 0 || $totalMinutes <= 0) {
            return [];
        }

        $blocks = [];
        foreach ($weights as $subjectId => $weight) {
            $minutes = max(15, (int) (round($totalMinutes * $weight / $sum / 5) * 5));
            $count = (int) ceil($minutes / $maxBlock);
            $each = (int) (round($minutes / $count / 5) * 5);
            $blocks[$subjectId] = array_fill(0, $count, max(5, $each));
        }

        $items = [];
        while ($blocks !== []) {
            foreach (array_keys($blocks) as $subjectId) {
                $items[] = ['subject_id' => $subjectId, 'target_min' => array_shift($blocks[$subjectId])];
                if ($blocks[$subjectId] === []) {
                    unset($blocks[$subjectId]);
                }
            }
        }

        return $items;
    }

    /** @param  list<array{subject_id: int, target_min: int}>  $items */
    public function replaceItems(StudyCycle $cycle, array $items): void
    {
        DB::transaction(function () use ($cycle, $items) {
            $cycle->items()->delete();
            foreach ($items as $position => $item) {
                Subject::findOrFail($item['subject_id']);
                CycleItem::create([
                    'study_cycle_id' => $cycle->id,
                    'subject_id' => $item['subject_id'],
                    'target_min' => max(5, min(600, $item['target_min'])),
                    'position' => $position,
                ]);
            }
            $cycle->update(['round' => 1, 'round_started_at' => CarbonImmutable::now()]);
        });
    }

    private function closeRoundIfDone(StudyCycle $cycle): void
    {
        $items = $cycle->items()->get();
        if ($items->isNotEmpty() && $items->every(fn (CycleItem $i) => $i->isComplete())) {
            $cycle->items()->update(['done_seconds' => 0]);
            $cycle->update(['round' => $cycle->round + 1, 'round_started_at' => CarbonImmutable::now()]);
        }
    }
}
