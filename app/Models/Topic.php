<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\BelongsToUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $subject_id
 * @property string $title
 * @property string $status
 * @property int|null $difficulty
 * @property int|null $estimated_min
 * @property string|null $resource_url
 * @property string|null $notes
 * @property int $position
 * @property CarbonImmutable|null $done_on
 * @property-read Subject $subject
 */
class Topic extends Model
{
    use BelongsToUser;

    public const STATUSES = [
        'todo' => 'A estudar',
        'doing' => 'Estudando',
        'done' => 'Concluído',
    ];

    public const DIFFICULTIES = [
        1 => 'Fácil',
        2 => 'Médio',
        3 => 'Difícil',
    ];

    protected $fillable = ['subject_id', 'title', 'status', 'difficulty', 'estimated_min', 'resource_url', 'notes', 'position', 'done_on'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'todo',
        'position' => 0,
    ];

    protected function casts(): array
    {
        return [
            'difficulty' => 'integer',
            'estimated_min' => 'integer',
            'position' => 'integer',
            'done_on' => DateOnly::class,
        ];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    /** Marca/desmarca como concluído (registra o dia). */
    public function toggleDone(): void
    {
        $done = ! $this->isDone();
        $this->update([
            'status' => $done ? 'done' : 'todo',
            'done_on' => $done ? CarbonImmutable::today() : null,
        ]);
    }
}
