<?php

namespace App\Models;

use App\Casts\DateOnly;
use App\Models\Concerns\BelongsToUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avaliação, trabalho ou entrega com data (docs/especificacao.md §3.8).
 *
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property int|null $subject_id
 * @property string $title
 * @property string $type
 * @property CarbonImmutable $due_on
 * @property string|null $due_time
 * @property string $status
 * @property string|null $grade
 * @property string|null $max_grade
 * @property string $weight
 * @property string|null $notes
 * @property-read Course $course
 * @property-read Subject|null $subject
 */
class AgendaItem extends Model
{
    use BelongsToUser;

    /** tipo => [rótulo, ícone] */
    public const TYPES = [
        'exam' => ['Prova', 'clipboard'],
        'assignment' => ['Trabalho', 'pencil'],
        'presentation' => ['Apresentação', 'message'],
        'quiz' => ['Atividade / quiz', 'check'],
        'project' => ['Projeto', 'target'],
        'other' => ['Outro', 'calendar'],
    ];

    protected $fillable = ['course_id', 'subject_id', 'title', 'type', 'due_on', 'due_time', 'status', 'grade', 'max_grade', 'weight', 'notes'];

    /** @var array<string, mixed> */
    protected $attributes = ['status' => 'open', 'type' => 'exam', 'weight' => 1];

    protected function casts(): array
    {
        return [
            'due_on' => DateOnly::class,
            'grade' => 'decimal:2',
            'max_grade' => 'decimal:2',
            'weight' => 'decimal:2',
        ];
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /** @return BelongsTo<Subject, $this> */
    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function typeLabel(): string
    {
        return self::TYPES[$this->type][0] ?? 'Outro';
    }

    public function typeIcon(): string
    {
        return self::TYPES[$this->type][1] ?? 'calendar';
    }

    public function isDone(): bool
    {
        return $this->status === 'done';
    }

    public function isLate(): bool
    {
        return ! $this->isDone() && $this->due_on->lt(CarbonImmutable::today());
    }

    public function color(): string
    {
        return $this->subject->color ?? $this->course->color;
    }

    /** Nota na escala 0–10 (ou null se não lançada). */
    public function normalizedGrade(): ?float
    {
        if ($this->grade === null) {
            return null;
        }
        $max = (float) ($this->max_grade ?? 10);

        return $max > 0 ? round((float) $this->grade / $max * 10, 2) : null;
    }
}
