@props(['items' => [], 'format' => 'duration'])
{{-- Ranking em barras horizontais. items: [['name', 'detail'?, 'color'?, 'value', 'icon'?]] --}}
@php $max = max(1, collect($items)->max('value') ?? 1); @endphp
<ul {{ $attributes->merge(['class' => 'space-y-3']) }}>
    @foreach ($items as $it)
        <li>
            <div class="mb-1 flex items-baseline justify-between gap-3 text-sm">
                <span class="flex min-w-0 items-center gap-1.5">
                    @isset($it['icon'])<x-icon :name="$it['icon']" class="size-4 text-ink-soft" />@endisset
                    <span class="truncate font-semibold">{{ $it['name'] }}</span>
                    @if (! empty($it['detail']))<span class="truncate text-xs text-ink-soft">· {{ $it['detail'] }}</span>@endif
                </span>
                <span class="shrink-0 font-display font-bold">{{ $format === 'duration' ? \App\Support\Duration::human($it['value']) : $it['value'] }}</span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-sheet-2">
                <div class="h-full rounded-full" style="width: {{ $it['value'] / $max * 100 }}%; background: {{ $it['color'] ?? 'var(--pen)' }}"></div>
            </div>
        </li>
    @endforeach
</ul>
