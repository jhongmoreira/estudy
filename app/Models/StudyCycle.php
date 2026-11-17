<?php

namespace App\Models;

use App\Models\Concerns\BelongsToUser;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Ciclo de estudos: sequência rotativa de disciplinas com tempo-alvo por bloco (docs/especificacao.md §3.7).
 *
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property bool $is_active
 * @property int $round
 * @property CarbonImmutable|null $round_started_at
 */
class StudyCycle extends Model
{
    use BelongsToUser;

    protected $fillable = ['name', 'is_active', 'round', 'round_started_at'];

    /** @var array<string, mixed> */
    protected $attributes = ['is_active' => false, 'round' => 1];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'round' => 'integer', 'round_started_at' => 'immutable_datetime'];
    }

    /** @return HasMany<CycleItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(CycleItem::class)->orderBy('position')->orderBy('id');
    }

    /** Primeiro item ainda não concluído nesta volta. */
    public function currentItem(): ?CycleItem
    {
        return $this->items->first(fn (CycleItem $i) => ! $i->isComplete());
    }
}
