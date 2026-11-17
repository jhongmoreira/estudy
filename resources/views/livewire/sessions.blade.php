<div x-data>
    <x-ui.page-header title="Sessões" :back="route('more')" subtitle="Seu histórico de estudo">
        <x-ui.button type="button" icon="plus" class="!px-4 !py-2.5" @click="$dispatch('log-session', {})">Registrar</x-ui.button>
    </x-ui.page-header>

    <div class="mb-4 grid grid-cols-2 gap-2 sm:grid-cols-3">
        <select wire:model.live="period" aria-label="Período" class="rounded-xl border-2 border-rule bg-sheet px-3 py-2.5 text-sm font-semibold outline-none focus:border-pen">
            @foreach (\App\Livewire\Sessions::PERIODS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
        <select wire:model.live="courseId" aria-label="Curso" class="rounded-xl border-2 border-rule bg-sheet px-3 py-2.5 text-sm font-semibold outline-none focus:border-pen">
            <option value="">Todos os cursos</option>
            @foreach ($courses as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
        <select wire:model.live="kind" aria-label="Tipo" class="col-span-2 rounded-xl border-2 border-rule bg-sheet px-3 py-2.5 text-sm font-semibold outline-none focus:border-pen sm:col-span-1">
            <option value="">Todos os tipos</option>
            @foreach (\App\Models\StudySession::KINDS as $key => [$label])<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
    </div>

    <div class="mb-6 flex items-baseline gap-2 rounded-2xl bg-sheet-2 px-4 py-3">
        <a href="{{ route('export.sessions', $period !== 'all' ? ['de' => now()->subDays((int) $period - 1)->toDateString()] : []) }}" class="order-last ml-auto text-sm font-semibold text-pen">Exportar CSV</a>
        <span class="font-display text-2xl font-bold">{{ \App\Support\Duration::human($total) }}</span>
        <span class="text-sm text-ink-soft">em {{ $count }} {{ $count === 1 ? 'sessão' : 'sessões' }}</span>
    </div>

    @if ($days->isEmpty())
        <x-ui.empty icon="clock" title="Nenhuma sessão no período" text="Use o cronômetro ou registre um estudo feito fora do app.">
            <x-ui.button type="button" icon="plus" @click="$dispatch('log-session', {})">Registrar estudo</x-ui.button>
        </x-ui.empty>
    @else
        <div class="space-y-6">
            @foreach ($days as $date => $items)
                @php $d = \Carbon\CarbonImmutable::parse($date); @endphp
                <section wire:key="day-{{ $date }}">
                    <div class="mb-2 flex items-baseline justify-between">
                        <h2 class="font-display font-bold">
                            @if ($d->isToday()) Hoje @elseif ($d->isYesterday()) Ontem @else {{ \Illuminate\Support\Str::ucfirst($d->translatedFormat('l, j \d\e M')) }} @endif
                        </h2>
                        <span class="text-sm font-semibold text-ink-soft">{{ \App\Support\Duration::human((int) ($dayTotals[$date] ?? 0)) }}</span>
                    </div>
                    <x-ui.card class="divide-y divide-rule">
                        @foreach ($items as $s)
                            @php $c = $s->subject->color ?? $s->subject->course->color; @endphp
                            <button type="button" wire:key="s-{{ $s->id }}" @click="$dispatch('log-session', { id: {{ $s->id }} })" class="tap flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-sheet-2">
                                <span class="grid size-10 shrink-0 place-items-center rounded-xl" style="background: color-mix(in srgb, {{ $c }} 15%, transparent); color: {{ $c }}"><x-icon :name="$s->kindIcon()" /></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold">{{ $s->subject->name }}</span>
                                    <span class="block truncate text-xs text-ink-soft">
                                        {{ $s->topic?->title ?? $s->subject->course->name }}
                                        @if ($s->questions_total) · {{ $s->questions_correct }}/{{ $s->questions_total }} questões @endif
                                        @if ($s->pages) · {{ $s->pages }} pág. @endif
                                    </span>
                                </span>
                                <span class="shrink-0 text-right">
                                    <span class="block font-display font-bold">{{ \App\Support\Duration::human($s->duration_seconds) }}</span>
                                    <span class="block text-xs text-ink-soft">{{ $s->started_at?->format('H:i') ?? 'manual' }}</span>
                                </span>
                            </button>
                        @endforeach
                    </x-ui.card>
                </section>
            @endforeach
        </div>
        @if ($hasMore)
            <x-ui.button type="button" variant="secondary" wire:click="more" class="mt-6 w-full">Carregar mais</x-ui.button>
        @endif
    @endif
</div>
