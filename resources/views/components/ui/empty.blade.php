@props(['icon' => 'book', 'title', 'text' => null])
<div {{ $attributes->merge(['class' => 'ruled flex flex-col items-center rounded-2xl border border-dashed border-rule bg-sheet px-6 py-10 text-center']) }}>
    <span class="mb-4 grid size-14 place-items-center rounded-2xl bg-marker/40 text-marker-ink"><x-icon :name="$icon" class="size-7" /></span>
    <h3 class="text-lg font-bold">{{ $title }}</h3>
    @if ($text)
        <p class="mt-1 max-w-xs text-sm text-ink-soft">{{ $text }}</p>
    @endif
    @if (! $slot->isEmpty())
        <div class="mt-5">{{ $slot }}</div>
    @endif
</div>
