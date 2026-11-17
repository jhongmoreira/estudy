@props(['totals' => [], 'weeks' => 8, 'goal' => 3600, 'min' => 900, 'weekStart' => 1, 'cell' => 14, 'gap' => 3, 'scale' => 1.6])
{{-- Calendário de constância em SVG (renderizado no servidor). totals: ['AAAA-MM-DD' => segundos] --}}
@php
    $today = \Carbon\CarbonImmutable::today();
    $end = \App\Support\Week::of($today, $weekStart)->end;
    $start = $end->subDays($weeks * 7 - 1);
    $w = $weeks * ($cell + $gap) - $gap;
    $h = 7 * ($cell + $gap) - $gap;
    $pad = 2; // margem para o contorno do dia de hoje não ser cortado nas bordas
    $fill = function (int $s) use ($goal, $min) {
        return match (true) {
            $s <= 0 => 'var(--sheet-2)',
            $s < $min => 'color-mix(in srgb, var(--pen) 25%, transparent)',
            $s < $goal => 'color-mix(in srgb, var(--pen) 65%, transparent)',
            default => 'var(--marker)',
        };
    };
@endphp
<svg viewBox="-{{ $pad }} -{{ $pad }} {{ $w + 2 * $pad }} {{ $h + 2 * $pad }}" width="{{ round(($w + 2 * $pad) * $scale) }}" height="{{ round(($h + 2 * $pad) * $scale) }}" {{ $attributes->merge(['class' => 'block h-auto max-w-full']) }} role="img" aria-label="Calendário de constância das últimas {{ $weeks }} semanas">
    @for ($i = 0; $i < $weeks * 7; $i++)
        @php
            $d = $start->addDays($i);
            $s = (int) ($totals[$d->toDateString()] ?? 0);
        @endphp
        @if ($d->lte($today))
            <rect x="{{ intdiv($i, 7) * ($cell + $gap) }}" y="{{ ($i % 7) * ($cell + $gap) }}" width="{{ $cell }}" height="{{ $cell }}" rx="3"
                  fill="{{ $fill($s) }}" @if ($d->isSameDay($today)) stroke="var(--pen)" stroke-width="1.5" @endif>
                <title>{{ $d->translatedFormat('j \d\e M') }}: {{ $s > 0 ? \App\Support\Duration::human($s) : 'sem estudo' }}</title>
            </rect>
        @endif
    @endfor
</svg>
