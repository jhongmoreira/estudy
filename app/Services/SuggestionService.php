<?php

namespace App\Services;

use App\Models\StudySession;
use App\Models\Topic;
use App\Support\Duration;
use App\Support\Week;
use Carbon\CarbonImmutable;

/** "O que estudar agora" (docs/especificacao.md §3.6). */
class SuggestionService
{
    public function __construct(private CycleService $cycles, private GoalService $goals) {}

    /**
     * @return list<array{title: string, detail: string, reason: string, color: string, subject_id: int, topic_id: int|null, kind: string}>
     */
    public function forToday(Week $week, int $limit = 4): array
    {
        $today = CarbonImmutable::today();
        $items = [];
        $seen = [];

        $add = function (array $item) use (&$items, &$seen) {
            $key = $item['subject_id'].'-'.($item['topic_id'] ?? 0);
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $items[] = $item;
            }
        };

        // 1. Ciclo ativo: bloco atual
        $cycle = $this->cycles->active();
        if ($cycle && ($current = $cycle->currentItem())) {
            $s = $current->subject;
            $add([
                'title' => $s->name, 'detail' => $s->course->name,
                'reason' => 'Ciclo: faltam '.Duration::human($current->remainingSeconds()).' neste bloco',
                'color' => $s->color ?? $s->course->color, 'subject_id' => $s->id, 'topic_id' => $this->nextTopicId($s->id), 'kind' => 'theory',
            ]);
        }

        // 2. Prazos da agenda nos próximos 3 dias: entra na etapa 6

        // 3. Metas semanais atrasadas
        foreach ($this->goals->deficits($week, $today)->take(2) as $d) {
            $s = $d['subject'];
            $add([
                'title' => $s->name, 'detail' => $s->course->name,
                'reason' => 'Meta da semana: faltam '.Duration::human($d['deficit']).' para ficar em dia',
                'color' => $s->color ?? $s->course->color, 'subject_id' => $s->id, 'topic_id' => $this->nextTopicId($s->id), 'kind' => 'theory',
            ]);
        }

        // 4. Continuar o último tópico em andamento
        $last = StudySession::with(['topic', 'subject.course'])->whereNotNull('topic_id')
            ->whereHas('topic', fn ($q) => $q->where('status', 'doing'))
            ->whereHas('subject.course', fn ($q) => $q->where('status', 'active'))
            ->latest('date')->latest('id')->first();
        if ($last && $last->topic) {
            $s = $last->subject;
            $add([
                'title' => $last->topic->title, 'detail' => $s->name,
                'reason' => 'Continuar de onde parou',
                'color' => $s->color ?? $s->course->color, 'subject_id' => $s->id, 'topic_id' => $last->topic_id, 'kind' => $last->kind === 'review' ? 'theory' : $last->kind,
            ]);
        }

        return array_slice($items, 0, $limit);
    }

    private function nextTopicId(int $subjectId): ?int
    {
        return Topic::where('subject_id', $subjectId)->where('status', '!=', 'done')
            ->orderByRaw("CASE WHEN status = 'doing' THEN 0 ELSE 1 END")->orderBy('position')->orderBy('id')->value('id');
    }
}
