<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\BelongsToUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Revisão espaçada de um tópico (docs/especificacao.md §3.4). No máximo 1 pendente por tópico.
 *
 * @property int $id
 * @property int $user_id
 * @property int $topic_id
 * @property int $subject_id
 * @property int|null $origin_session_id
 * @property int|null $session_id
 * @property int $step
 * @property CarbonImmutable $due_on
 * @property string $status
 * @property string|null $rating
 * @property CarbonImmutable|null $done_on
 * @property-read Topic $topic
 * @property-read Subject $subject
 */
class Review extends Model
{
    use BelongsToUser;

    /** avaliação => [rótulo, emoji] */
    public const RATINGS = [
        'good' => ['Lembrei bem', '😀'],
        'hard' => ['Com dificuldade', '😐'],
        'forgot' => ['Esqueci', '😵'],
    ];

    protected $fillable = ['topic_id', 'subject_id', 'origin_session_id', 'session_id', 'step', 'due_on', 'status', 'rating', 'done_on'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'pending',
        'step' => 1,
    ];

    protected function casts(): array
    {
        return [
            'step' => 'integer',
            'due_on' => DateOnly::class,
            'done_on' => DateOnly::class,
        ];
    }

    /** @return BelongsTo<Topic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    /** @param  Builder<Review>  $query */
    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    /**
     * Pendentes vencidas ou de hoje.
     *
     * @param  Builder<Review>  $query
     */
    public function scopeDue(Builder $query): void
    {
        $query->where('status', 'pending')->where('due_on', '<=', CarbonImmutable::today()->toDateString());
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_on->lt(CarbonImmutable::today());
    }
}
