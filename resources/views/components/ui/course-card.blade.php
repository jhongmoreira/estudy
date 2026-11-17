@props(['course', 'stats' => null, 'week' => null])
{{-- Card de curso: faixa na cor, tipo, progresso de tópicos --}}
@php $s = $stats ?? ['subjects' => 0, 'total' => 0, 'done' => 0, 'progress' => 0]; @endphp
<a href="{{ route('courses.show', $course) }}" wire:navigate
   {{ $attributes->merge(['class' => 'tap group relative block min-w-0 overflow-hidden rounded-2xl border border-rule bg-sheet p-4 pl-5 hover:bg-sheet-2']) }}>
    <span class="absolute inset-y-0 left-0 w-1.5" style="background: {{ $course->color }}"></span>
    <div class="flex items-start gap-3">
        <span class="grid size-11 shrink-0 place-items-center rounded-xl text-xl" style="background: color-mix(in srgb, {{ $course->color }} 15%, transparent); color: {{ $course->color }}">
            @if ($course->emoji) {{ $course->emoji }} @else <x-icon :name="$course->typeIcon()" class="size-6" /> @endif
        </span>
        <div class="min-w-0 flex-1">
            <p class="truncate font-display text-lg font-bold leading-tight">{{ $course->name }}</p>
            <p class="truncate text-sm text-ink-soft">{{ $course->typeLabel() }}@if ($course->institution) · {{ $course->institution }}@endif</p>
        </div>
        @if ($s['total'] > 0)
            <span class="font-display text-lg font-bold" style="color: {{ $s['progress'] >= 1 ? 'var(--done)' : $course->color }}">{{ (int) floor($s['progress'] * 100) }}%</span>
        @endif
    </div>
    <div class="mt-3">
        <x-ui.progress :value="$s['progress']" :max="1" :color="$course->color" />
        @if ($week !== null && ($week > 0 || $course->weekly_goal_min))
            <p class="mt-2 flex items-center gap-1 text-xs font-semibold">
                <x-icon name="clock" class="size-3.5 text-ink-soft" />
                {{ \App\Support\Duration::human($week) }}@if ($course->weekly_goal_min) <span class="font-normal text-ink-soft">de {{ \App\Support\Duration::human($course->weekly_goal_min * 60) }} na semana</span>@else <span class="font-normal text-ink-soft">nesta semana</span>@endif
                @if ($course->weekly_goal_min && $week >= $course->weekly_goal_min * 60) <span class="rounded bg-marker px-1 text-[10px] text-[#1f2a44]">meta!</span> @endif
            </p>
        @endif
        <p class="mt-2 text-xs text-ink-soft">
            @if ($s['total'] > 0)
                {{ $s['done'] }} de {{ $s['total'] }} tópicos · {{ $s['subjects'] }} {{ mb_strtolower($course->unitLabel($s['subjects'] !== 1)) }}
            @elseif ($s['subjects'] > 0)
                {{ $s['subjects'] }} {{ mb_strtolower($course->unitLabel($s['subjects'] !== 1)) }} · sem tópicos ainda
            @else
                Sem conteúdo ainda — toque para adicionar
            @endif
        </p>
    </div>
</a>
