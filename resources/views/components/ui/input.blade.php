@props(['label' => null, 'name', 'type' => 'text', 'hint' => null, 'suffix' => null])
@php
    $model = $attributes->wire('model')->value() ?: $name;
    $id = $attributes->get('id', 'f-'.str_replace('.', '-', $model));
@endphp
<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-semibold">{{ $label }}</label>
    @endif
    <div class="relative">
        <input id="{{ $id }}" name="{{ $name }}" type="{{ $type }}"
            {{ $attributes->class([
                'block w-full rounded-xl border-2 bg-sheet px-4 py-3 text-ink placeholder:text-ink-soft/60 outline-none transition focus:border-pen',
                'pr-16' => $suffix,
                'border-late' => $errors->has($model),
                'border-rule' => ! $errors->has($model),
            ]) }}>
        @if ($suffix)
            <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm text-ink-soft">{{ $suffix }}</span>
        @endif
    </div>
    @error($model)
        <p class="mt-1.5 text-sm text-late">{{ $message }}</p>
    @elseif ($hint)
        <p class="mt-1.5 text-sm text-ink-soft">{{ $hint }}</p>
    @enderror
</div>
