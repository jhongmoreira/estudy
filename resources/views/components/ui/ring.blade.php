@props(['value' => 0, 'max' => 1, 'size' => 120, 'stroke' => 10, 'color' => 'var(--pen)'])
{{-- Anel de progresso em SVG. Quando completa, vira marca-texto. --}}
@php
    $pct = $max > 0 ? min(1, max(0, $value / $max)) : 0;
    $r = ($size - $stroke) / 2;
    $c = 2 * M_PI * $r;
    $done = $pct >= 1;
@endphp
<div {{ $attributes->merge(['class' => 'relative inline-grid place-items-center']) }} style="width: {{ $size }}px; height: {{ $size }}px"
     role="progressbar" aria-valuemin="0" aria-valuemax="{{ $max }}" aria-valuenow="{{ $value }}">
    <svg viewBox="0 0 {{ $size }} {{ $size }}" class="absolute inset-0 -rotate-90">
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $r }}" fill="none" stroke="var(--rule)" stroke-width="{{ $stroke }}" />
        <circle cx="{{ $size / 2 }}" cy="{{ $size / 2 }}" r="{{ $r }}" fill="none"
                stroke="{{ $done ? 'var(--marker)' : $color }}" stroke-width="{{ $stroke }}" stroke-linecap="round"
                stroke-dasharray="{{ $c }}" stroke-dashoffset="{{ $c * (1 - $pct) }}" class="transition-[stroke-dashoffset] duration-700" />
    </svg>
    <div class="relative text-center">{{ $slot }}</div>
</div>
