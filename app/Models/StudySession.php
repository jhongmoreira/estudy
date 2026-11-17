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
 * @property int $course_id
 * @property int $subject_id
 * @property int|null $topic_id
 * @property CarbonImmutable $date
 * @property CarbonImmutable|null $started_at
 * @property int $duration_seconds
 * @property string $kind
 * @property int|null $pages
 * @property int|null $questions_total
 * @property int|null $questions_correct
 * @property string $source
 * @property string|null $notes
 * @property-read Course $course
 * @property-read Subject $subject
 * @property-read Topic|null $topic
 */
class StudySession extends Model
{
    use BelongsToUser;

    /** tipo => [rótulo, ícone] */
    public const KINDS = [
        'theory' => ['Teoria / leitura', 'book'],
        'video' => ['Videoaula', 'monitor'],
        'class' => ['Aula', 'cap'],
        'exercises' => ['Exercícios', 'clipboard'],
        'review' => ['Revisão', 'review'],
        'practice' => ['Prática / projeto', 'target'],
        'writing' => ['Escrita / trabalho', 'pencil'],
    ];

    public const MIN_SECONDS = 60;

    public const MAX_SECONDS = 12 * 3600;

    protected $fillable = [
        'course_id', 'subject_id', 'topic_id', 'date', 'started_at', 'duration_seconds', 'kind',
        'pages', 'questions_total', 'questions_correct', 'source', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'date' => DateOnly::class,
            'started_at' => 'immutable_datetime',
            'duration_seconds' => 'integer',
            'pages' => 'integer',
            'questions_total' => 'integer',
            'questions_correct' => 'integer',
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

    /** @return BelongsTo<Topic, $this> */
    public function topic(): BelongsTo
    {
        return $this->belongsTo(Topic::class);
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind][0] ?? $this->kind;
    }

    public function kindIcon(): string
    {
        return self::KINDS[$this->kind][1] ?? 'book';
    }
}
