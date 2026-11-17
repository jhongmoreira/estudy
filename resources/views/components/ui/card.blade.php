@props(['stripe' => null])
{{-- Card "folha". stripe = cor da faixa lateral (curso/disciplina) --}}
<div {{ $attributes->class(['relative overflow-hidden rounded-2xl border border-rule bg-sheet', 'pl-1' => $stripe]) }}>
    @if ($stripe)
        <span class="absolute inset-y-0 left-0 w-1" style="background: {{ $stripe }}"></span>
    @endif
    {{ $slot }}
</div>
