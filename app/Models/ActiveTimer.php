<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Cronômetro em andamento (no máximo um por usuário). O servidor é a fonte da verdade;
 * o navegador só desenha o relógio a partir destes horários.
 *
 * @property int $id
 * @property int $user_id
 * @property int $subject_id
 * @property int|null $topic_id
 * @property string $kind
 * @property bool $pomodoro
 * @property CarbonImmutable $started_at
 * @property CarbonImmutable|null $paused_at
 * @property int $paused_seconds
 * @property string|null $notes
 * @property-read Subject $subject
 * @property-read Topic|null $topic
 */
class ActiveTimer extends Model
{
    use BelongsToUser;

    protected $fillable = ['subject_id', 'topic_id', 'kind', 'pomodoro', 'started_at', 'paused_at', 'paused_seconds', 'notes'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'paused_seconds' => 0,
        'pomodoro' => false,
    ];

    protected function casts(): array
    {
        return [
            'pomodoro' => 'boolean',
            'started_at' => 'immutable_datetime',
            'paused_at' => 'immutable_datetime',
            'paused_seconds' => 'integer',
        ];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @return BelongsTo<Topic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function isPaused(): bool
    {
        return $this->paused_at !== null;
    }

    /** Tempo líquido (sem pausas) em segundos. */
    public function elapsedSeconds(?CarbonImmutable $now = null): int
    {
        $now ??= CarbonImmutable::now();
        $end = $this->paused_at ?? $now;
        $seconds = $end->getTimestamp() - $this->started_at->getTimestamp() - $this->paused_seconds;

        return max(0, $seconds);
    }

    /**
     * Estado para o relógio no navegador (milissegundos).
     *
     * @return array{startedAt: int, pausedAt: int|null, pausedSeconds: int, serverNow: int}
     */
    public function clientState(): array
    {
        return [
            'startedAt' => $this->started_at->getTimestampMs(),
            'pausedAt' => $this->paused_at?->getTimestampMs(),
            'pausedSeconds' => $this->paused_seconds,
            'serverNow' => CarbonImmutable::now()->getTimestampMs(),
        ];
    }
}
