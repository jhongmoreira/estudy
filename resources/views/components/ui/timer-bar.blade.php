{{-- Barra do cronômetro em andamento (aparece em todas as telas do app) --}}
@php $timer = \App\Models\ActiveTimer::with('subject.course')->first(); @endphp
@if ($timer)
    @php $color = $timer->subject->color ?? $timer->subject->course->color; @endphp
    <a href="{{ route('focus') }}" wire:navigate
       x-data="timerClock(null, null, false, 0, 0, '', @js($timer->clientState()))"
       class="tap fixed inset-x-3 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] z-30 flex items-center gap-3 rounded-2xl bg-ink px-4 py-3 text-paper shadow-xl lg:inset-x-auto lg:bottom-6 lg:right-6 lg:w-80">
        <span class="relative flex size-3 shrink-0">
            <span class="absolute inset-0 rounded-full opacity-60 motion-safe:animate-ping" style="background: {{ $color }}" x-show="!paused"></span>
            <span class="relative size-3 rounded-full" style="background: {{ $color }}"></span>
        </span>
        <span class="min-w-0 flex-1 truncate text-sm font-semibold">{{ $timer->subject->name }}</span>
        <span class="font-display text-lg font-bold tabular-nums" x-text="display">{{ \App\Support\Duration::clock($timer->elapsedSeconds()) }}</span>
        <span class="text-xs font-semibold uppercase" x-show="paused" x-cloak>pausado</span>
    </a>
@endif
