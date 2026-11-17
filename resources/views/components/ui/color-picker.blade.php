@props(['label' => 'Cor', 'nullable' => false, 'nullLabel' => 'Mesma do curso'])
<div>
    <p class="mb-1.5 text-sm font-semibold">{{ $label }}</p>
    <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="{{ $label }}">
        @if ($nullable)
            <label class="tap relative grid h-9 cursor-pointer place-items-center rounded-full border-2 border-dashed border-rule px-3 text-xs font-semibold text-ink-soft has-[:checked]:border-pen has-[:checked]:text-pen">
                <input type="radio" value="" {{ $attributes->whereStartsWith('wire:model') }} class="sr-only">{{ $nullLabel }}
            </label>
        @endif
        @foreach (\App\Support\Palette::COLORS as $hex => $name)
            <label class="tap relative grid size-9 cursor-pointer place-items-center rounded-full ring-offset-2 ring-offset-sheet has-[:checked]:ring-2 has-[:checked]:ring-ink"
                   style="background-color: {{ $hex }}; color: {{ \App\Support\Palette::onColor($hex) }}" title="{{ $name }}">
                <input type="radio" value="{{ $hex }}" {{ $attributes->whereStartsWith('wire:model') }} class="peer sr-only" aria-label="{{ $name }}">
                <x-icon name="check" class="hidden size-4 peer-checked:block" />
            </label>
        @endforeach
    </div>
</div>
