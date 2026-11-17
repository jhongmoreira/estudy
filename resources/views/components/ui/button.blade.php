@props(['variant' => 'primary', 'href' => null, 'type' => 'submit', 'icon' => null, 'navigate' => true])
@php
    $classes = [
        'tap inline-flex items-center justify-center gap-2 rounded-2xl px-5 py-3 font-semibold disabled:opacity-60',
        'bg-pen text-white shadow-sm hover:bg-pen-strong dark:text-[#14161c]' => $variant === 'primary',
        'border border-rule bg-sheet text-ink hover:bg-sheet-2' => $variant === 'secondary',
        'bg-marker text-[#1f2a44] hover:brightness-95' => $variant === 'marker',
        'bg-late text-white hover:brightness-95' => $variant === 'danger',
        'text-pen hover:bg-pen-soft' => $variant === 'ghost',
    ];
@endphp
@if ($href)
    <a href="{{ $href }}" @if ($navigate) wire:navigate @endif {{ $attributes->class($classes) }}>@if ($icon)<x-icon :name="$icon" />@endif{{ $slot }}</a>
@else
    <button type="{{ $type }}" wire:loading.attr="disabled" {{ $attributes->class($classes) }}>@if ($icon)<x-icon :name="$icon" />@endif{{ $slot }}</button>
@endif
