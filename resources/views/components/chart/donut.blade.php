@props(['items' => [], 'size' => 160])
{{-- Rosca em SVG. items: [['name', 'color', 'value']] --}}
@php
    $total = max(1, collect($items)->sum('value'));
    $r = 15.9155; // circunferência = 100
    $offset = 25;
@endphp
<div {{ $attributes->merge(['class' => 'flex flex-wrap items-center gap-5']) }}>
    <svg viewBox="0 0 42 42" width="{{ $size }}" height="{{ $size }}" class="shrink-0" role="img" aria-label="Distribuição do tempo">
        <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="var(--sheet-2)" stroke-width="6" />
        @foreach ($items as $it)
            @php $pct = $it['value'] / $total * 100; @endphp
            <circle cx="21" cy="21" r="{{ $r }}" fill="none" stroke="{{ $it['color'] }}" stroke-width="6"
                    stroke-dasharray="{{ $pct }} {{ 100 - $pct }}" stroke-dashoffset="{{ $offset }}">
                <title>{{ $it['name'] }}: {{ \App\Support\Duration::human($it['value']) }} ({{ round($pct) }}%)</title>
            </circle>
            @php $offset -= $pct; @endphp
        @endforeach
        <text x="21" y="22.5" text-anchor="middle" font-size="5" font-weight="700" fill="var(--ink)" font-family="ui-rounded, system-ui">{{ \App\Support\Duration::short(collect($items)->sum('value')) }}</text>
    </svg>
    <ul class="min-w-0 flex-1 space-y-1.5 text-sm">
        @foreach ($items as $it)
            <li class="flex items-center gap-2">
                <span class="size-3 shrink-0 rounded-sm" style="background: {{ $it['color'] }}"></span>
                <span class="min-w-0 flex-1 truncate">{{ $it['name'] }}</span>
                <span class="shrink-0 font-semibold">{{ round($it['value'] / $total * 100) }}%</span>
            </li>
        @endforeach
    </ul>
</div>
