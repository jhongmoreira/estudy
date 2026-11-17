<?php

namespace App\Services;

use App\Models\AgendaItem;
use App\Models\Subject;
use Illuminate\Support\Collection;

/** Médias e "quanto preciso tirar" (docs/especificacao.md §3.8). Escala 0–10. */
class GradeService
{
    /**
     * status: none | no_target | approved | on_track | at_risk | impossible
     *
     * @param  Collection<int, AgendaItem>|null  $items  itens da disciplina (carrega se não vier)
     * @return array{average: float|null, graded_weight: float, pending_weight: float, needed: float|null, status: string}
     */
    public function summary(Subject $subject, ?Collection $items = null): array
    {
        $items ??= AgendaItem::where('subject_id', $subject->id)->get();
        $graded = $items->filter(fn (AgendaItem $i) => $i->grade !== null);
        $pending = $items->filter(fn (AgendaItem $i) => $i->grade === null);

        $gradedWeight = (float) $graded->sum(fn (AgendaItem $i) => (float) $i->weight);
        $pendingWeight = (float) $pending->sum(fn (AgendaItem $i) => (float) $i->weight);
        $points = (float) $graded->sum(fn (AgendaItem $i) => ($i->normalizedGrade() ?? 0) * (float) $i->weight);

        $average = $gradedWeight > 0 ? round($points / $gradedWeight, 2) : null;
        $target = $subject->passing_grade !== null ? (float) $subject->passing_grade : null;

        if ($average === null && $pendingWeight === 0.0) {
            return ['average' => null, 'graded_weight' => 0.0, 'pending_weight' => 0.0, 'needed' => null, 'status' => 'none'];
        }
        if ($target === null) {
            return ['average' => $average, 'graded_weight' => $gradedWeight, 'pending_weight' => $pendingWeight, 'needed' => null, 'status' => 'no_target'];
        }

        $totalWeight = $gradedWeight + $pendingWeight;
        $needed = $pendingWeight > 0 ? round(($target * $totalWeight - $points) / $pendingWeight, 2) : null;

        $status = match (true) {
            $pendingWeight === 0.0 => $average >= $target ? 'approved' : 'at_risk',
            $needed <= 0 => 'approved',
            $needed > 10 => 'impossible',
            $average !== null && $average >= $target => 'on_track',
            default => 'at_risk',
        };

        return ['average' => $average, 'graded_weight' => $gradedWeight, 'pending_weight' => $pendingWeight, 'needed' => $needed !== null ? max(0, $needed) : null, 'status' => $status];
    }
}
