<?php

namespace App\Models;

use App\Support\Palette;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property array<string, mixed>|null $settings
 * @property Carbon|null $onboarded_at
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /** Preferências padrão (coluna JSON "settings"). Ver docs/especificacao.md §2. */
    public const DEFAULT_SETTINGS = [
        'theme' => 'system',              // system | light | dark
        'daily_goal_min' => 60,           // meta diária em minutos
        'streak_min' => 15,               // mínimo para o dia contar na sequência
        'study_days' => [1, 2, 3, 4, 5, 6], // 0 = domingo
        'week_starts_on' => 1,            // 0 = domingo, 1 = segunda
        'pomodoro' => ['focus' => 25, 'short' => 5, 'long' => 15, 'every' => 4], // minutos; pausa longa a cada N focos
    ];

    public const THEMES = ['system', 'light', 'dark'];

    /** @var array<string, mixed> */
    protected $attributes = [
        'settings' => null,
        'onboarded_at' => null,
    ];

    protected $fillable = [
        'name',
        'email',
        'password',
        'settings',
        'onboarded_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'onboarded_at' => 'datetime',
            'password' => 'hashed',
            'settings' => 'array',
        ];
    }

    /** @return HasMany<Course, $this> */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function setting(string $key): mixed
    {
        return Arr::get($this->settings ?? [], $key, self::DEFAULT_SETTINGS[$key] ?? null);
    }

    /** @param  array<string, mixed>  $values */
    public function updateSettings(array $values): void
    {
        $settings = $this->settings ?? [];
        foreach ($values as $key => $value) {
            Arr::set($settings, $key, $value);
        }
        $this->update(['settings' => $settings]);
    }

    public function theme(): string
    {
        $theme = $this->setting('theme');

        return in_array($theme, self::THEMES, true) ? $theme : 'system';
    }

    public function dailyGoalMinutes(): int
    {
        return max(5, min(720, (int) $this->setting('daily_goal_min')));
    }

    /** @return list<int> */
    public function studyDays(): array
    {
        $days = array_values(array_unique(array_map('intval', (array) $this->setting('study_days'))));
        $days = array_values(array_filter($days, fn (int $d) => $d >= 0 && $d <= 6));
        sort($days);

        return $days ?: [0, 1, 2, 3, 4, 5, 6];
    }

    public function studiesOn(\DateTimeInterface $date): bool
    {
        return in_array((int) $date->format('w'), $this->studyDays(), true);
    }

    /** @return array{focus: int, short: int, long: int, every: int} */
    public function pomodoro(): array
    {
        $p = (array) $this->setting('pomodoro') + self::DEFAULT_SETTINGS['pomodoro'];

        return [
            'focus' => max(5, min(120, (int) $p['focus'])),
            'short' => max(1, min(30, (int) $p['short'])),
            'long' => max(5, min(60, (int) $p['long'])),
            'every' => max(2, min(8, (int) $p['every'])),
        ];
    }

    public function weekStartsOn(): int
    {
        return (int) $this->setting('week_starts_on') === 0 ? 0 : 1;
    }

    public function initials(): string
    {
        return Str::of($this->name)->explode(' ')->filter()
            ->map(fn (string $part) => Str::upper(Str::substr($part, 0, 1)))
            ->take(2)->implode('');
    }

    public function firstName(): string
    {
        return Str::before($this->name, ' ') ?: $this->name;
    }

    /** Cor estável do avatar (derivada do id). */
    public function avatarColor(): string
    {
        $colors = array_keys(Palette::COLORS);

        return $colors[$this->id % count($colors)];
    }
}
