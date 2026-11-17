<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Disciplina (graduação/pós) ou módulo (demais tipos de curso).
 *
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property string $name
 * @property string|null $color
 * @property string|null $term
 * @property string $status
 * @property int|null $weekly_goal_min
 * @property string|null $passing_grade
 * @property string|null $notes
 * @property int $position
 * @property-read Course $course
 */
class Subject extends Model
{
    use BelongsToUser;

    public const STATUSES = [
        'active' => 'Em andamento',
        'completed' => 'Concluída',
        'archived' => 'Arquivada',
    ];

    protected $fillable = ['course_id', 'name', 'color', 'term', 'status', 'weekly_goal_min', 'passing_grade', 'notes', 'position'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
        'position' => 0,
    ];

    protected function casts(): array
    {
        return [
            'weekly_goal_min' => 'integer',
            'passing_grade' => 'decimal:2',
            'position' => 'integer',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return HasMany<Topic, $this> */
    public function topics(): HasMany
    {
        return $this->hasMany(Topic::class)->orderBy('position')->orderBy('id');
    }

    /** Cor própria ou a do curso. */
    public function displayColor(): string
    {
        return $this->color ?? $this->course->color;
    }
}
