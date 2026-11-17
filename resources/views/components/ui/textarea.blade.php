@props(['label' => null, 'name', 'hint' => null, 'rows' => 3])
@php
    $model = $attributes->wire('model')->value() ?: $name;
    $id = $attributes->get('id', 'f-'.str_replace('.', '-', $model));
@endphp
<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-semibold">{{ $label }}</label>
    @endif
    <textarea id="{{ $id }}" name="{{ $name }}" rows="{{ $rows }}"
        {{ $attributes->class([
            'block w-full rounded-xl border-2 bg-sheet px-4 py-3 text-ink placeholder:text-ink-soft/60 outline-none transition focus:border-pen',
            'border-late' => $errors->has($model),
            'border-rule' => ! $errors->has($model),
        ]) }}></textarea>
    @error($model)
        <p class="mt-1.5 text-sm text-late">{{ $message }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-sm text-ink-soft">{{ $hint }}</p>
    @enderror
</div>
