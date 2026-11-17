<div x-data>
    <x-ui.page-header title="Agenda" :back="route('more')" subtitle="Provas, trabalhos e entregas">
        <x-ui.button type="button" icon="plus" class="!px-4 !py-2.5" @click="$dispatch('edit-agenda', {})">Novo</x-ui.button>
    </x-ui.page-header>

    <x-ui.segmented action="setView" class="mb-6 max-w-xs" :value="$view" :options="['lista' => ['Lista', 'list'], 'mes' => ['Mês', 'calendar']]" />

    @if ($view === 'lista')
        @if ($late->isEmpty() && $week->isEmpty() && $later->isEmpty() && $done->isEmpty())
            <x-ui.empty icon="calendar" title="Agenda vazia" text="Cadastre provas, trabalhos e entregas para receber lembretes e acompanhar suas notas.">
                <x-ui.button type="button" icon="plus" @click="$dispatch('edit-agenda', {})">Adicionar</x-ui.button>
            </x-ui.empty>
        @else
            <div class="space-y-6">
                @foreach ([['Atrasados', $late, 'text-late'], ['Próximos 7 dias', $week, ''], ['Mais adiante', $later, '']] as [$label, $items, $cls])
                    @if ($items->isNotEmpty())
                        <section class="space-y-3">
                            <h2 class="text-lg font-bold {{ $cls }}">{{ $label }}</h2>
                            <x-ui.card class="divide-y divide-rule">
                                @foreach ($items as $item) <x-ui.agenda-row :item="$item" /> @endforeach
                            </x-ui.card>
                        </section>
                    @endif
                @endforeach
                @if ($done->isNotEmpty())
                    <section class="space-y-3">
                        <h2 class="text-lg font-bold text-ink-soft">Feitos recentemente</h2>
                        <x-ui.card class="divide-y divide-rule">
                            @foreach ($done as $item) <x-ui.agenda-row :item="$item" /> @endforeach
                        </x-ui.card>
                    </section>
                @endif
            </div>
        @endif
    @else
        {{-- Visão de mês --}}
        <div class="rounded-2xl border border-rule bg-sheet p-3">
            <div class="mb-3 flex items-center justify-between">
                <button type="button" wire:click="shiftMonth(-1)" class="tap rounded-full p-2 hover:bg-sheet-2" aria-label="Mês anterior"><x-icon name="chevron-left" /></button>
                <p class="font-display text-lg font-bold">{{ \Illuminate\Support\Str::ucfirst($monthStart->translatedFormat('F \d\e Y')) }}</p>
                <button type="button" wire:click="shiftMonth(1)" class="tap rounded-full p-2 hover:bg-sheet-2" aria-label="Próximo mês"><x-icon name="chevron-right" /></button>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold uppercase text-ink-soft">
                @foreach (['seg', 'ter', 'qua', 'qui', 'sex', 'sáb', 'dom'] as $wd)<span>{{ $wd }}</span>@endforeach
            </div>
            <div class="mt-1 grid grid-cols-7 gap-1">
                @for ($i = 0; $i < 42; $i++)
                    @php
                        $d = $gridStart->addDays($i);
                        $items = $monthItems[$d->toDateString()] ?? collect();
                        $inMonth = $d->month === $monthStart->month;
                    @endphp
                    <button type="button" @if ($items->isNotEmpty()) @click="$dispatch('edit-agenda', { id: {{ $items->first()->id }} })" @else @click="$dispatch('edit-agenda', {})" @endif
                            @class(['flex min-h-14 flex-col items-center gap-1 rounded-xl p-1 text-sm hover:bg-sheet-2',
                                    'text-ink-soft/40' => ! $inMonth,
                                    'bg-pen-soft font-bold text-pen' => $d->isToday()])
                            title="{{ $items->pluck('title')->implode(' · ') }}">
                        <span>{{ $d->day }}</span>
                        <span class="flex flex-wrap justify-center gap-0.5">
                            @foreach ($items->take(3) as $it)
                                <span class="size-1.5 rounded-full" style="background: {{ $it->color() }}"></span>
                            @endforeach
                        </span>
                    </button>
                @endfor
            </div>
        </div>
        @php $monthList = $monthItems->flatten()->filter(fn ($i) => $i->due_on->month === $monthStart->month)->sortBy('due_on'); @endphp
        @if ($monthList->isNotEmpty())
            <x-ui.card class="mt-4 divide-y divide-rule">
                @foreach ($monthList as $item) <x-ui.agenda-row :item="$item" /> @endforeach
            </x-ui.card>
        @endif
    @endif
</div>
