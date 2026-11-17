<div>
    <x-ui.page-header title="Revisões" subtitle="Retome o que estudou para não esquecer" />

    @php $dueCount = $overdue->count() + $today->count(); @endphp
    <div class="mb-6 grid grid-cols-3 gap-2">
        <div class="rounded-2xl border border-rule bg-sheet p-3 text-center">
            <p class="font-display text-2xl font-bold {{ $overdue->isNotEmpty() ? 'text-late' : '' }}">{{ $overdue->count() }}</p>
            <p class="text-xs text-ink-soft">atrasadas</p>
        </div>
        <div class="rounded-2xl border border-rule bg-sheet p-3 text-center">
            <p class="font-display text-2xl font-bold text-pen">{{ $today->count() }}</p>
            <p class="text-xs text-ink-soft">para hoje</p>
        </div>
        <div class="rounded-2xl border border-rule bg-sheet p-3 text-center">
            <p class="font-display text-2xl font-bold">{{ $doneThisWeek }}</p>
            <p class="text-xs text-ink-soft">feitas em 7 dias</p>
        </div>
    </div>

    @if ($dueCount === 0 && $week->isEmpty() && $later->isEmpty())
        <x-ui.empty icon="review" title="Nenhuma revisão agendada"
                    text="Ao estudar um tópico, o Estudy agenda as revisões automaticamente ({{ implode(', ', auth()->user()->reviewIntervals()) }} dias depois) e lembra você no dia certo." />
    @else
        <div class="space-y-6">
            @if ($overdue->isNotEmpty())
                <section class="space-y-3">
                    <div class="flex items-end justify-between gap-3">
                        <h2 class="text-lg font-bold text-late">Atrasadas</h2>
                        <button type="button" wire:click="rescheduleOverdue" class="tap flex items-center gap-1 text-sm font-semibold text-pen">
                            <x-icon name="calendar" class="size-4" /> Reagendar
                        </button>
                    </div>
                    <x-ui.card class="divide-y divide-rule border-late/40">
                        @foreach ($overdue as $r) @include('livewire.partials.review-actions', ['r' => $r]) @endforeach
                    </x-ui.card>
                </section>
            @endif

            <section class="space-y-3">
                <h2 class="text-lg font-bold"><span class="highlight">Hoje</span></h2>
                @if ($today->isEmpty())
                    <p class="rounded-2xl bg-sheet-2 px-4 py-4 text-sm text-ink-soft">{{ $overdue->isEmpty() ? 'Nada para revisar hoje. 🎉' : 'Nenhuma nova para hoje — dê conta das atrasadas.' }}</p>
                @else
                    <x-ui.card class="divide-y divide-rule">
                        @foreach ($today as $r) @include('livewire.partials.review-actions', ['r' => $r]) @endforeach
                    </x-ui.card>
                @endif
            </section>

            @if ($week->isNotEmpty())
                <section class="space-y-3">
                    <h2 class="text-lg font-bold">Próximos 7 dias</h2>
                    <x-ui.card class="divide-y divide-rule">
                        @foreach ($week as $r)
                            <x-ui.review-row :review="$r" :steps="$steps">
                                <button type="button" wire:click="rate({{ $r->id }})" class="tap rounded-xl px-3 py-2 text-sm font-semibold text-pen hover:bg-pen-soft">Antecipar</button>
                            </x-ui.review-row>
                        @endforeach
                    </x-ui.card>
                </section>
            @endif

            @if ($later->isNotEmpty())
                <section class="space-y-3">
                    <button type="button" wire:click="$toggle('showFuture')" class="flex items-center gap-2 text-lg font-bold">
                        Futuras <span class="text-sm font-semibold text-ink-soft">{{ $later->count() }}</span>
                        <x-icon :name="$showFuture ? 'arrow-up' : 'arrow-down'" class="size-4 text-ink-soft" />
                    </button>
                    @if ($showFuture)
                        <x-ui.card class="divide-y divide-rule">
                            @foreach ($later as $r) <x-ui.review-row :review="$r" :steps="$steps" /> @endforeach
                        </x-ui.card>
                    @endif
                </section>
            @endif
        </div>
    @endif

    <x-ui.sheet name="rate-review" title="Como foi a revisão?">
        @if ($selected)
            <p class="mb-1 font-semibold">{{ $selected->topic->title }}</p>
            <p class="mb-5 text-sm text-ink-soft">{{ $selected->subject->name }} · revisão {{ min($selected->step, $steps) }} de {{ $steps }}</p>
        @endif
        <div class="grid gap-2">
            @foreach (\App\Models\Review::RATINGS as $key => [$label, $emoji])
                <button type="button" wire:click="complete('{{ $key }}')" class="tap flex items-center gap-3 rounded-2xl border-2 border-rule px-4 py-3 text-left hover:border-pen hover:bg-pen-soft">
                    <span class="text-2xl">{{ $emoji }}</span>
                    <span>
                        <span class="block font-semibold">{{ $label }}</span>
                        <span class="block text-xs text-ink-soft">{{ ['good' => 'Avança para o próximo intervalo', 'hard' => 'Repete o mesmo intervalo', 'forgot' => 'Recomeça as revisões amanhã'][$key] }}</span>
                    </span>
                </button>
            @endforeach
        </div>
    </x-ui.sheet>

    <x-ui.sheet name="move-review" title="Mudar data">
        <form wire:submit="move" class="space-y-4">
            <x-ui.input label="Revisar em" name="moveDate" type="date" wire:model="moveDate" :min="now()->toDateString()" />
            <x-ui.button class="w-full" icon="check">Salvar</x-ui.button>
        </form>
    </x-ui.sheet>
</div>
