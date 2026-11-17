<?php

namespace App\Support;

use Carbon\CarbonImmutable;

/** Semana do usuário (começa no domingo ou na segunda, conforme a configuração). */
final class Week
{
    public function __construct(
        public readonly CarbonImmutable $start,
        public readonly CarbonImmutable $end,
    ) {}

    public static function of(\DateTimeInterface $date, int $startsOn = 1): self
    {
        $day = CarbonImmutable::instance($date)->startOfDay();
        $offset = ((int) $day->format('w') - $startsOn + 7) % 7;
        $start = $day->subDays($offset);

        return new self($start, $start->addDays(6));
    }

    /** @return list<CarbonImmutable> */
    public function days(): array
    {
        return array_map(fn (int $i) => $this->start->addDays($i), range(0, 6));
    }
}
