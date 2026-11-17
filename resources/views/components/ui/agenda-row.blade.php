@props(['item', 'toggle' => true, 'showCourse' => true])
@php
    $today = \Carbon\CarbonImmutable::today();
    $d = $item->due_on;
    $days = (int) $today->diffInDays($d, false);
    $when = match (true) {
        $days === 0 => 'hoje',
        $days === 1 => 'amanhã',
        $days === -1 => 'ontem',
        $days > 1 && $days < 7 => \Illuminate\Support\Str::lower($d->translatedFormat('l')),
        default => $d->translatedFormat('j \d\e M'),
    };
@endphp
<div class="flex items-center gap-1 pr-2" x-data wire:key="ag-{{ $item->id }}">
    @if ($toggle)
        <button type="button" wire:click="toggleDone({{ $item->id }})" class="tap grid size-12 shrink-0 place-items-center" aria-label="{{ $item->isDone() ? 'Reabrir' : 'Marcar como feito' }}">
            <span @class(['grid size-6 place-items-center rounded-full border-2', 'border-done bg-done text-white dark:text-[#14161c]' => $item->isDone(), 'border-rule' => ! $item->isDone()])>
                @if ($item->isDone())<x-icon name="check" class="size-4" />@endif
            </span>
        </button>
    @endif
    <button type="button" @click="$dispatch('edit-agenda', { id: {{ $item->id }} })" @class(['flex min-w-0 flex-1 items-center gap-3 py-3 text-left', 'pl-4' => ! $toggle])>
        <span class="grid size-10 shrink-0 place-items-center rounded-xl" style="background: color-mix(in srgb, {{ $item->color() }} 15%, transparent); color: {{ $item->color() }}">
            <x-icon :name="$item->typeIcon()" />
        </span>
        <span class="min-w-0 flex-1">
            <span @class(['block truncate font-semibold', 'text-ink-soft line-through' => $item->isDone() && $item->grade === null])>{{ $item->title }}</span>
            <span class="block truncate text-xs text-ink-soft">
                {{ $item->typeLabel() }} · {{ $item->subject?->name ?? ($showCourse ? $item->course->name : '') }}
            </span>
        </span>
        <span class="shrink-0 text-right">
            @if ($item->grade !== null)
                <span class="block font-display font-bold">{{ rtrim(rtrim(number_format((float) $item->grade, 2, ',', ''), '0'), ',') }}<span class="text-xs font-normal text-ink-soft">/{{ rtrim(rtrim(number_format((float) ($item->max_grade ?? 10), 2, ',', ''), '0'), ',') }}</span></span>
            @endif
            <span @class(['block text-xs font-semibold', 'text-late' => $item->isLate(), 'text-pen' => ! $item->isLate() && $days >= 0 && $days <= 2 && ! $item->isDone(), 'text-ink-soft' => $item->isDone() || $days > 2])>
                {{ $when }}@if ($item->due_time) · {{ $item->due_time }}@endif
            </span>
        </span>
    </button>
</div>
