@props(['icon' => null, 'label', 'description' => null, 'href' => null, 'chevron' => false, 'tone' => 'pen', 'navigate' => true])
@php
    $tones = [
        'pen' => 'bg-pen-soft text-pen',
        'marker' => 'bg-marker/30 text-marker-ink',
        'late' => 'bg-late/10 text-late',
        'soft' => 'bg-sheet-2 text-ink-soft',
    ];
    $tag = $href ? 'a' : 'button';
@endphp
<{{ $tag }} @if ($href) href="{{ $href }}" @if ($navigate) wire:navigate @endif @else type="button" @endif
    {{ $attributes->merge(['class' => 'tap flex w-full items-center gap-3 px-4 py-3.5 text-left hover:bg-sheet-2']) }}>
    @if ($icon)
        <span class="grid size-10 shrink-0 place-items-center rounded-xl {{ $tones[$tone] ?? $tones['pen'] }}"><x-icon :name="$icon" /></span>
    @endif
    <span class="min-w-0 flex-1">
        <span class="block font-semibold">{{ $label }}</span>
        @if ($description)
            <span class="block text-sm text-ink-soft">{{ $description }}</span>
        @endif
    </span>
    {{ $slot }}
    @if ($chevron || $href)
        <x-icon name="chevron-right" class="size-5 text-ink-soft" />
    @endif
</{{ $tag }}>
