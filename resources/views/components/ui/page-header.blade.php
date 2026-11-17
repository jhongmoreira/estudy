@props(['title', 'back' => null, 'subtitle' => null])
{{-- Cabeçalho de página: título grande, à esquerda (estilo caderno) --}}
<header class="flex items-center gap-2 pb-4 pt-5 lg:pt-8">
    @if ($back)
        <a href="{{ $back }}" wire:navigate class="tap -ml-2 rounded-full p-2 text-ink-soft hover:bg-sheet-2" aria-label="Voltar">
            <x-icon name="chevron-left" class="size-6" />
        </a>
    @endif
    <div class="min-w-0 flex-1">
        <h1 class="truncate text-2xl font-bold lg:text-3xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="text-sm text-ink-soft">{{ $subtitle }}</p>
        @endif
    </div>
    {{ $slot }}
</header>
