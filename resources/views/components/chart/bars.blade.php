@props(['bars' => [], 'height' => 160])
{{-- Barras verticais empilhadas, sem JS. bars: [['label','title','total','segments' => [['value','color','name']]]] --}}
@php
    $max = max(1, collect($bars)->max('total') ?? 1);
    $every = max(1, (int) ceil(count($bars) / 12));
@endphp
<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="flex items-end gap-[2px]" style="height: {{ $height }}px" role="img" aria-label="Gráfico de tempo estudado">
        @foreach ($bars as $bar)
            <div class="group relative flex h-full min-w-0 flex-1 flex-col justify-end" title="{{ $bar['title'] }}: {{ \App\Support\Duration::human($bar['total']) }}">
                <div class="flex flex-col-reverse overflow-hidden rounded-t-[3px]" style="height: {{ $bar['total'] / $max * 100 }}%">
                    @foreach ($bar['segments'] as $seg)
                        <div style="height: {{ $bar['total'] > 0 ? $seg['value'] / $bar['total'] * 100 : 0 }}%; background: {{ $seg['color'] }}"></div>
                    @endforeach
                </div>
                @if ($bar['total'] === 0)<div class="h-[2px] rounded bg-rule"></div>@endif
            </div>
        @endforeach
    </div>
    <div class="mt-1 flex gap-[2px] text-[10px] text-ink-soft">
        @foreach ($bars as $i => $bar)
            <span class="min-w-0 flex-1 text-center">{{ $i % $every === 0 ? $bar['label'] : '' }}</span>
        @endforeach
    </div>
    <table class="sr-only">
        <caption>Tempo estudado</caption>
        @foreach ($bars as $bar)<tr><th>{{ $bar['title'] }}</th><td>{{ \App\Support\Duration::human($bar['total']) }}</td></tr>@endforeach
    </table>
</div>
