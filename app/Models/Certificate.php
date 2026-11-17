<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Certificado de um curso (arquivo em storage privado, baixado por rota autenticada).
 *
 * @property int $id
 * @property int $user_id
 * @property int $course_id
 * @property string $file_path
 * @property string $original_name
 * @property string $mime
 * @property int $size
 * @property-read Course $course
 */
class Certificate extends Model
{
    use BelongsToUser;

    public const MIMES = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];

    public const MAX_KB = 5120;

    protected $fillable = ['course_id', 'file_path', 'original_name', 'mime', 'size'];

    protected static function booted(): void
    {
        static::deleted(fn (Certificate $c) => Storage::disk('local')->delete($c->file_path));
    }

    /** @return BelongsTo<Course, $this> */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
