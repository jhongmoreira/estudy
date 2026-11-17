<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $user_id
 * @property int $study_cycle_id
 * @property int $subject_id
 * @property int $target_min
 * @property int $done_seconds
 * @property int $position
 * @property-read Subject $subject
 */
class CycleItem extends Model
{
    use BelongsToUser;

    protected $fillable = ['study_cycle_id', 'subject_id', 'target_min', 'done_seconds', 'position'];

    /** @var array<string, mixed> */
    protected $attributes = ['done_seconds' => 0, 'position' => 0];

    protected function casts(): array
    {
        return ['target_min' => 'integer', 'done_seconds' => 'integer', 'position' => 'integer'];
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function isComplete(): bool
    {
        return $this->done_seconds >= $this->target_min * 60;
    }

    public function remainingSeconds(): int
    {
        return max(0, $this->target_min * 60 - $this->done_seconds);
    }
}
