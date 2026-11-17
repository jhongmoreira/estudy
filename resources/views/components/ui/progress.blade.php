@props(['value' => 0, 'max' => 1, 'color' => 'var(--pen)'])
@php $pct = $max > 0 ? min(100, max(0, $value / $max * 100)) : 0; @endphp
<div {{ $attributes->merge(['class' => 'h-1.5 w-full overflow-hidden rounded-full bg-sheet-2']) }} role="progressbar" aria-valuenow="{{ round($pct) }}" aria-valuemin="0" aria-valuemax="100">
    <div class="h-full rounded-full transition-[width] duration-500" style="width: {{ $pct }}%; background: {{ $pct >= 100 ? 'var(--marker)' : $color }}"></div>
</div>
