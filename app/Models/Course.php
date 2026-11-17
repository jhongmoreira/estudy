<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\BelongsToUser;
use Carbon\CarbonImmutable;
use Database\Factories\CourseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property string $type
 * @property string|null $institution
 * @property string $color
 * @property string|null $emoji
 * @property string $status
 * @property CarbonImmutable|null $started_on
 * @property CarbonImmutable|null $target_on
 * @property CarbonImmutable|null $completed_on
 * @property int|null $workload_hours
 * @property int|null $weekly_goal_min
 * @property string|null $url
 * @property string|null $notes
 * @property int $position
 */
class Course extends Model
{
    use BelongsToUser;

    /** @use HasFactory<CourseFactory> */
    use HasFactory;

    /** tipo => [rótulo, ícone, nome das divisões (singular, plural)] */
    public const TYPES = [
        'undergrad' => ['Graduação', 'cap', 'Disciplina', 'Disciplinas'],
        'postgrad' => ['Pós / MBA', 'cap', 'Disciplina', 'Disciplinas'],
        'masters' => ['Mestrado', 'cap', 'Disciplina', 'Disciplinas'],
        'doctorate' => ['Doutorado', 'cap', 'Disciplina', 'Disciplinas'],
        'free' => ['Curso livre / online', 'monitor', 'Módulo', 'Módulos'],
        'extension' => ['Extensão', 'award', 'Módulo', 'Módulos'],
        'language' => ['Idioma', 'message', 'Módulo', 'Módulos'],
        'certification' => ['Certificação', 'award', 'Módulo', 'Módulos'],
        'self' => ['Autodidata', 'sparkles', 'Módulo', 'Módulos'],
        'other' => ['Outro', 'book', 'Módulo', 'Módulos'],
    ];

    public const STATUSES = [
        'active' => 'Em andamento',
        'planned' => 'Planejado',
        'paused' => 'Pausado',
        'completed' => 'Concluído',
        'dropped' => 'Abandonado',
    ];

    protected $fillable = [
        'name', 'type', 'institution', 'color', 'emoji', 'status', 'started_on', 'target_on', 'completed_on',
        'workload_hours', 'weekly_goal_min', 'url', 'notes', 'position',
    ];

    /** @var array<string, mixed> */
    protected $attributes = [
        'status' => 'active',
        'position' => 0,
    ];

    protected static function booted(): void
    {
        static::deleting(fn (Course $c) => $c->certificate?->delete());
    }

    protected function casts(): array
    {
        return [
            'started_on' => DateOnly::class,
            'target_on' => DateOnly::class,
            'completed_on' => DateOnly::class,
            'workload_hours' => 'integer',
            'weekly_goal_min' => 'integer',
            'position' => 'integer',
        ];
    }

    /** @return HasMany<Subject, $this> */
    public function subjects(): HasMany
    {
        return $this->hasMany(Subject::class)->orderBy('position')->orderBy('id');
    }

    /** @return HasManyThrough<Topic, Subject, $this> */
    public function topics(): HasManyThrough
    {
        return $this->hasManyThrough(Topic::class, Subject::class);
    }

    /** @return HasOne<Certificate, $this> */
    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    /** @param  Builder<Course>  $query */
    public function scopeOrdered(Builder $query): void
    {
        $query->orderBy('position')->orderBy('id');
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? 'Curso';
    }

    public function typeIcon(): string
    {
        return self::TYPES[$this->type][1] ?? 'book';
    }

    /** "Disciplina" (graduação/pós) ou "Módulo" (demais). */
    public function unitLabel(bool $plural = false): string
    {
        return self::TYPES[$this->type][$plural ? 3 : 2] ?? ($plural ? 'Módulos' : 'Módulo');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function isAcademic(): bool
    {
        return in_array($this->type, ['undergrad', 'postgrad', 'masters', 'doctorate'], true);
    }
}
