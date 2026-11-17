<?php

use App\Support\Duration;
use App\Support\Palette;
use App\Support\Week;
use Carbon\CarbonImmutable;

it('formata durações', function () {
    expect(Duration::human(0))->toBe('0 min')
        ->and(Duration::human(45 * 60))->toBe('45 min')
        ->and(Duration::human(3600))->toBe('1h')
        ->and(Duration::human(3900))->toBe('1h05')
        ->and(Duration::clock(2537))->toBe('00:42:17')
        ->and(Duration::short(1500))->toBe('25m')
        ->and(Duration::short(3900))->toBe('1h05');
});

it('interpreta durações digitadas', function (string $input, ?int $minutes) {
    expect(Duration::parse($input))->toBe($minutes === null ? null : $minutes * 60);
})->with([
    ['90', 90], ['1h30', 90], ['1h', 60], ['1:30', 90], ['45min', 45], ['45 min', 45], ['1,5h', 90], ['2h05m', 125], ['abc', null], ['', null],
]);

it('calcula a semana a partir de segunda ou domingo', function () {
    $wednesday = CarbonImmutable::parse('2026-11-18');

    expect(Week::of($wednesday, 1)->start->toDateString())->toBe('2026-11-16')
        ->and(Week::of($wednesday, 1)->end->toDateString())->toBe('2026-11-22')
        ->and(Week::of($wednesday, 0)->start->toDateString())->toBe('2026-11-15')
        ->and(Week::of(CarbonImmutable::parse('2026-11-15'), 1)->start->toDateString())->toBe('2026-11-09');
});

it('escolhe texto legível sobre cada cor da paleta', function () {
    expect(Palette::onColor('#3047c9'))->toBe('#ffffff')
        ->and(Palette::onColor('#ffe066'))->toBe('#1f2a44')
        ->and(Palette::isValid('#3047C9'))->toBeTrue()
        ->and(Palette::isValid('#000000'))->toBeFalse();
});
