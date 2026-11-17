<div x-data>
    <x-ui.page-header title="Ciclo de estudos" :back="route('more')" subtitle="Rodízio de disciplinas, sem data fixa" />

    @if (! $cycle || $items->isEmpty())
        <x-ui.empty icon="cycle" title="Monte seu ciclo"
                    text="Em vez de horários fixos, você estuda as disciplinas em sequência, cada uma por um tempo-alvo. Perdeu um dia? Continua de onde parou.">
            <x-ui.button type="button" icon="sparkles" wire:click="openBuilder">Montar com o assistente</x-ui.button>
        </x-ui.empty>
    @else
        {{-- Volta atual --}}
        <div class="rounded-2xl border border-rule bg-sheet p-5">
            <div class="flex items-center gap-4">
                <x-ui.ring :value="$roundDone" :max="max(1, $roundTotal)" :size="84" :stroke="8">
                    <span class="font-display text-lg font-bold">{{ $roundTotal > 0 ? (int) floor($roundDone / $roundTotal * 100) : 0 }}%</span>
                </x-ui.ring>
                <div class="min-w-0 flex-1">
                    <p class="truncate font-display text-xl font-bold">{{ $cycle->name }}</p>
                    <p class="text-sm text-ink-soft">Volta {{ $cycle->round }} · {{ \App\Support\Duration::human($roundDone) }} de {{ \App\Support\Duration::human($roundTotal) }}</p>
                    @unless ($cycle->is_active)<p class="mt-1 text-sm font-semibold text-late">Pausado</p>@endunless
                </div>
            </div>
            @if ($current)
                @php $c = $current->subject->color ?? $current->subject->course->color; @endphp
                <a href="{{ route('study', ['disciplina' => $current->subject_id]) }}" wire:navigate
                   class="tap mt-4 flex items-center gap-3 rounded-2xl bg-pen p-4 text-white dark:text-[#14161c]">
                    <span class="min-w-0 flex-1">
                        <span class="block text-xs font-semibold uppercase tracking-wide opacity-80">Agora</span>
                        <span class="block truncate font-display text-lg font-bold">{{ $current->subject->name }}</span>
                        <span class="block text-sm opacity-80">faltam {{ \App\Support\Duration::human($current->remainingSeconds()) }} neste bloco</span>
                    </span>
                    <x-icon name="play" class="size-8" />
                </a>
            @endif
        </div>

        <section class="mt-8 space-y-3">
            <div class="flex items-end justify-between gap-3">
                <h2 class="text-lg font-bold"><span class="highlight">Blocos</span></h2>
                <button type="button" wire:click="$toggle('editing')" class="tap flex items-center gap-1 text-sm font-semibold text-pen">
                    <x-icon :name="$editing ? 'check' : 'pencil'" class="size-4" /> {{ $editing ? 'Concluir' : 'Editar' }}
                </button>
            </div>
            <x-ui.card class="divide-y divide-rule">
                @foreach ($items as $item)
                    @php
                        $c = $item->subject->color ?? $item->subject->course->color;
                        $isCurrent = $current && $current->id === $item->id;
                    @endphp
                    <div @class(['flex items-center gap-3 px-4 py-3', 'bg-pen-soft/60' => $isCurrent]) wire:key="ci-{{ $item->id }}">
                        <span class="w-5 text-center text-sm font-bold text-ink-soft">{{ $loop->iteration }}</span>
                        <div class="min-w-0 flex-1">
                            <p @class(['truncate font-semibold', 'text-ink-soft line-through' => $item->isComplete()])>{{ $item->subject->name }}</p>
                            <div class="mt-1 flex items-center gap-2">
                                <x-ui.progress :value="$item->done_seconds" :max="$item->target_min * 60" :color="$c" class="max-w-48" />
                                <span class="shrink-0 text-xs text-ink-soft">{{ \App\Support\Duration::human(min($item->done_seconds, $item->target_min * 60)) }}/{{ \App\Support\Duration::human($item->target_min * 60) }}</span>
                            </div>
                        </div>
                        @if ($editing)
                            <input type="number" min="5" max="600" value="{{ $item->target_min }}" aria-label="Minutos"
                                   wire:change="updateMinutes({{ $item->id }}, $event.target.value)"
                                   class="w-16 rounded-lg border-2 border-rule bg-sheet px-2 py-1 text-center text-sm outline-none focus:border-pen">
                            <button type="button" wire:click="move({{ $item->id }}, -1)" class="tap rounded-lg p-1.5 hover:bg-sheet-2" aria-label="Subir"><x-icon name="arrow-up" class="size-4" /></button>
                            <button type="button" wire:click="move({{ $item->id }}, 1)" class="tap rounded-lg p-1.5 hover:bg-sheet-2" aria-label="Descer"><x-icon name="arrow-down" class="size-4" /></button>
                            <button type="button" wire:click="removeItem({{ $item->id }})" class="tap rounded-lg p-1.5 text-late hover:bg-sheet-2" aria-label="Remover"><x-icon name="x" class="size-4" /></button>
                        @elseif (! $item->isComplete())
                            <button type="button" wire:click="skip({{ $item->id }})" class="tap rounded-lg px-2 py-1 text-xs font-semibold text-ink-soft hover:bg-sheet-2">Pular</button>
                        @endif
                    </div>
                @endforeach
            </x-ui.card>

            @if ($editing)
                <form wire:submit="addItem" class="flex flex-wrap items-end gap-2 rounded-2xl border-2 border-dashed border-rule p-3">
                    <select wire:model="newSubjectId" aria-label="Disciplina" class="min-w-0 flex-1 rounded-xl border-2 border-rule bg-sheet px-3 py-2.5 text-sm outline-none focus:border-pen">
                        <option value="">Adicionar disciplina…</option>
                        @foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->course->name }} · {{ $s->name }}</option>@endforeach
                    </select>
                    <input type="number" wire:model="newMinutes" min="5" max="600" aria-label="Minutos" class="w-20 rounded-xl border-2 border-rule bg-sheet px-2 py-2.5 text-center text-sm outline-none focus:border-pen">
                    <x-ui.button icon="plus" class="!px-4 !py-2.5">Adicionar</x-ui.button>
                    @error('newSubjectId') <p class="w-full text-sm text-late">{{ $message }}</p> @enderror
                </form>
            @endif
        </section>

        <div class="mt-6 flex flex-wrap gap-2">
            <x-ui.button type="button" variant="secondary" icon="sparkles" wire:click="openBuilder">Remontar</x-ui.button>
            <x-ui.button type="button" variant="secondary" icon="review" wire:click="restart" wire:confirm="Zerar o progresso desta volta?">Reiniciar volta</x-ui.button>
            <x-ui.button type="button" variant="ghost" :icon="$cycle->is_active ? 'pause' : 'play'" wire:click="toggleActive">{{ $cycle->is_active ? 'Pausar ciclo' : 'Ativar ciclo' }}</x-ui.button>
        </div>
    @endif

    <x-ui.sheet name="cycle-builder" title="Montar ciclo">
        <form wire:submit="build" class="space-y-5">
            <x-ui.input label="Nome" name="name" wire:model="name" maxlength="80" />
            <div>
                <p class="mb-1 text-sm font-semibold">Peso de cada disciplina</p>
                <p class="mb-2 text-xs text-ink-soft">0 = fora do ciclo. Dê mais peso ao que é mais importante ou mais difícil para você.</p>
                @if ($subjects->isEmpty())
                    <p class="rounded-xl bg-sheet-2 px-3 py-3 text-sm text-ink-soft">Cadastre disciplinas em cursos em andamento primeiro.</p>
                @endif
                <div class="max-h-64 space-y-1.5 overflow-y-auto">
                    @foreach ($subjects as $s)
                        <div class="flex items-center gap-2 rounded-xl bg-sheet-2 px-3 py-2" wire:key="w-{{ $s->id }}">
                            <span class="size-2.5 shrink-0 rounded-full" style="background: {{ $s->color ?? $s->course->color }}"></span>
                            <span class="min-w-0 flex-1 truncate text-sm"><span class="font-semibold">{{ $s->name }}</span> <span class="text-ink-soft">· {{ $s->course->name }}</span></span>
                            <select wire:model.live="weights.{{ $s->id }}" aria-label="Peso de {{ $s->name }}" class="rounded-lg border-2 border-rule bg-sheet px-2 py-1 text-sm outline-none focus:border-pen">
                                @foreach ([0, 1, 2, 3, 4, 5] as $w)<option value="{{ $w }}">{{ $w }}</option>@endforeach
                            </select>
                        </div>
                    @endforeach
                </div>
                @error('weights') <p class="mt-1.5 text-sm text-late">{{ $message }}</p> @enderror
            </div>
            <div class="grid grid-cols-2 gap-3">
                <x-ui.input label="Horas por volta" name="totalHours" type="number" inputmode="numeric" min="1" max="100" wire:model.live.debounce.400ms="totalHours" suffix="h" />
                <x-ui.input label="Bloco máximo" name="maxBlock" type="number" inputmode="numeric" min="15" max="240" wire:model.live.debounce.400ms="maxBlock" suffix="min" />
            </div>
            @if ($preview !== [])
                <div class="rounded-2xl border border-rule bg-paper p-3 text-sm">
                    <p class="mb-2 font-semibold">Prévia: {{ count($preview) }} blocos</p>
                    <ol class="max-h-40 list-decimal space-y-0.5 overflow-y-auto pl-5">
                        @foreach ($preview as $p)<li>{{ $subjectNames[$p['subject_id']] ?? '?' }} — {{ \App\Support\Duration::human($p['target_min'] * 60) }}</li>@endforeach
                    </ol>
                </div>
            @endif
            <x-ui.button class="w-full" icon="check">{{ $cycle ? 'Substituir blocos' : 'Criar ciclo' }}</x-ui.button>
        </form>
    </x-ui.sheet>
</div>
