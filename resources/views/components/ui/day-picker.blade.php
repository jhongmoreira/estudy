@props(['selected' => [], 'action' => 'toggleDay'])
{{-- Escolha dos dias de estudo (D S T Q Q S S) --}}
@php $labels = ['D', 'S', 'T', 'Q', 'Q', 'S', 'S']; $names = ['domingo', 'segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado']; @endphp
<div class="grid max-w-md grid-cols-7 gap-2">
    @foreach ([1, 2, 3, 4, 5, 6, 0] as $day)
        @php $on = in_array($day, $selected, true); @endphp
        <button type="button" wire:click="{{ $action }}({{ $day }})" aria-pressed="{{ $on ? 'true' : 'false' }}" aria-label="{{ $names[$day] }}"
                @class(['tap aspect-square rounded-xl border-2 font-display text-lg font-bold',
                        'border-pen bg-pen text-white dark:text-[#14161c]' => $on,
                        'border-rule bg-sheet text-ink-soft' => ! $on])>{{ $labels[$day] }}</button>
    @endforeach
</div>
