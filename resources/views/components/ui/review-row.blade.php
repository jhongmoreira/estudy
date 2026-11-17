@props(['review', 'steps' => 3])
@php
    $c = $review->subject->color ?? $review->subject->course->color;
    $today = \Carbon\CarbonImmutable::today();
    $days = (int) $today->diffInDays($review->due_on, false);
    $when = match (true) {
        $days < -1 => 'atrasada há '.abs($days).' dias',
        $days === -1 => 'atrasada desde ontem',
        $days === 0 => 'hoje',
        $days === 1 => 'amanhã',
        $days < 7 => \Illuminate\Support\Str::lower($review->due_on->translatedFormat('l')),
        default => $review->due_on->translatedFormat('j \d\e M'),
    };
@endphp
<div class="flex items-center gap-3 px-4 py-3" wire:key="review-{{ $review->id }}">
    <span class="h-10 w-1.5 shrink-0 rounded-full" style="background: {{ $c }}"></span>
    <div class="min-w-0 flex-1">
        <p class="line-clamp-2 font-semibold leading-snug">{{ $review->topic->title }}</p>
        <p class="truncate text-xs text-ink-soft">
            {{ $review->subject->name }} ·
            <span @class(['font-semibold text-late' => $days < 0])>{{ $when }}</span> ·
            revisão {{ min($review->step, $steps) }} de {{ $steps }}
        </p>
    </div>
    {{ $slot }}
</div>
