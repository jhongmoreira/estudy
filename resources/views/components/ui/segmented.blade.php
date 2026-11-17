@props(['options' => [], 'value' => null, 'action'])
{{-- options: ['valor' => ['rótulo', 'ícone']] ; chama $wire.action('valor') --}}
<div {{ $attributes->merge(['class' => 'grid gap-1 rounded-2xl bg-sheet-2 p-1']) }} style="grid-template-columns: repeat({{ count($options) }}, minmax(0, 1fr))">
    @foreach ($options as $key => [$label, $icon])
        <button type="button" wire:click="{{ $action }}('{{ $key }}')" aria-pressed="{{ $value === $key ? 'true' : 'false' }}"
                @class(['tap flex items-center justify-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold',
                        'bg-sheet text-pen shadow-sm' => $value === $key,
                        'text-ink-soft' => $value !== $key])>
            @if ($icon)<x-icon :name="$icon" class="size-4" />@endif {{ $label }}
        </button>
    @endforeach
</div>
