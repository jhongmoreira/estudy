@props(['label' => null, 'name', 'options' => [], 'placeholder' => null])
@php
    $model = $attributes->wire('model')->value() ?: $name;
    $id = $attributes->get('id', 'f-'.str_replace('.', '-', $model));
@endphp
<div>
    @if ($label)
        <label for="{{ $id }}" class="mb-1.5 block text-sm font-semibold">{{ $label }}</label>
    @endif
    <select id="{{ $id }}" name="{{ $name }}" {{ $attributes->class(['block w-full rounded-xl border-2 border-rule bg-sheet px-4 py-3 text-ink outline-none focus:border-pen']) }}>
        @if ($placeholder !== null)<option value="">{{ $placeholder }}</option>@endif
        @foreach ($options as $value => $text)
            <option value="{{ $value }}">{{ $text }}</option>
        @endforeach
    </select>
    @error($model) <p class="mt-1.5 text-sm text-late">{{ $message }}</p> @enderror
</div>
