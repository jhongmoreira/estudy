@php
    $isDisc = $course->unitLabel() === 'Disciplina';
    $filters = ['all' => 'Todos', 'todo' => 'Pendentes', 'done' => 'Concluídos', 'hard' => 'Difíceis'];
@endphp
<div x-data>
    <x-ui.page-header :title="$subject->name" :back="route('courses.show', $course)" :subtitle="$course->name.($subject->term ? ' · '.$subject->term : '')">
        <button type="button" @click="$dispatch('edit-subject', { courseId: {{ $course->id }}, id: {{ $subject->id }} })" class="tap rounded-full p-2.5 text-ink-soft hover:bg-sheet-2" aria-label="Editar">
            <x-icon name="pencil" />
        </button>
    </x-ui.page-header>

    {{-- Resumo --}}
    <div class="relative flex items-center gap-4 overflow-hidden rounded-2xl border border-rule bg-sheet p-4">
        <span class="absolute inset-y-0 left-0 w-1.5" style="background: {{ $color }}"></span>
        <x-ui.ring :value="$stats['progress']" :max="1" :size="72" :stroke="7" :color="$color">
            <span class="font-display text-base font-bold">{{ (int) floor($stats['progress'] * 100) }}%</span>
        </x-ui.ring>
        <div class="min-w-0 flex-1">
            <p class="font-display text-lg font-bold">{{ $stats['done'] }} de {{ $stats['total'] }} tópicos</p>
            <p class="text-sm text-ink-soft">{{ $stats['total'] - $stats['done'] }} {{ $stats['total'] - $stats['done'] === 1 ? 'falta' : 'faltam' }} para concluir</p>
        </div>
        <x-ui.button :href="route('study', ['disciplina' => $subject->id])" icon="play" class="!px-4 !py-2.5">Estudar</x-ui.button>
    </div>
    @if ($subject->notes)
        <p class="mt-3 whitespace-pre-line rounded-2xl bg-sheet-2 px-4 py-3 text-sm text-ink-soft">{{ $subject->notes }}</p>
    @endif

    {{-- Tópicos --}}
    <section class="mt-8 space-y-3">
        <div class="flex items-end justify-between gap-3">
            <h2 class="text-lg font-bold"><span class="highlight">Tópicos</span></h2>
            <div class="flex items-center gap-3">
                @if ($topics->count() > 1 && $filter === 'all')
                    <button type="button" wire:click="$toggle('organizing')" class="tap flex items-center gap-1 text-sm font-semibold text-pen">
                        <x-icon :name="$organizing ? 'check' : 'arrows'" class="size-4" /> {{ $organizing ? 'Concluir' : 'Organizar' }}
                    </button>
                @endif
                <button type="button" @click="$dispatch('paste-list', { courseId: {{ $course->id }}, subjectId: {{ $subject->id }} })" class="tap flex items-center gap-1 text-sm font-semibold text-pen">
                    <x-icon name="clipboard" class="size-4" /> Colar lista
                </button>
            </div>
        </div>

        @if ($stats['total'] > 0)
            <div class="-mx-4 flex gap-2 overflow-x-auto px-4 lg:mx-0 lg:px-0">
                @foreach ($filters as $key => $label)
                    <button type="button" wire:click="$set('filter', '{{ $key }}')"
                            @class(['tap shrink-0 rounded-full px-3 py-1 text-sm font-semibold', 'bg-ink text-paper' => $filter === $key, 'bg-sheet-2 text-ink-soft' => $filter !== $key])>{{ $label }}</button>
                @endforeach
            </div>
        @endif

        @if ($topics->isEmpty() && $stats['total'] === 0)
            <x-ui.empty icon="list" title="Nenhum tópico ainda" text="Adicione aulas, capítulos ou assuntos — um por vez abaixo ou colando uma lista." />
        @elseif ($topics->isEmpty())
            <p class="rounded-2xl bg-sheet-2 px-4 py-6 text-center text-sm text-ink-soft">Nenhum tópico neste filtro.</p>
        @else
            <x-ui.card class="divide-y divide-rule">
                @foreach ($topics as $topic)
                    <div class="flex items-center gap-1 pr-2" wire:key="topic-{{ $topic->id }}">
                        <button type="button" wire:click="toggle({{ $topic->id }})" class="tap grid size-12 shrink-0 place-items-center" aria-label="{{ $topic->isDone() ? 'Desmarcar' : 'Concluir' }} {{ $topic->title }}">
                            <span @class(['grid size-6 place-items-center rounded-lg border-2 transition',
                                          'border-done bg-done text-white dark:text-[#14161c]' => $topic->isDone(),
                                          'border-rule' => ! $topic->isDone()])>
                                @if ($topic->isDone())<x-icon name="check" class="size-4" />@endif
                            </span>
                        </button>
                        <button type="button" wire:click="editTopic({{ $topic->id }})" class="min-w-0 flex-1 py-3 text-left">
                            <span @class(['block', 'text-ink-soft line-through decoration-2' => $topic->isDone()])>{{ $topic->title }}</span>
                            @if ($topic->status === 'doing' || $topic->estimated_min || $topic->notes || $topic->resource_url)
                                <span class="mt-0.5 flex flex-wrap items-center gap-x-3 text-xs text-ink-soft">
                                    @if ($topic->status === 'doing')<span class="rounded bg-marker/40 px-1.5 font-semibold text-marker-ink">estudando</span>@endif
                                    @if ($topic->estimated_min)<span class="flex items-center gap-1"><x-icon name="clock" class="size-3" />{{ \App\Support\Duration::human($topic->estimated_min * 60) }}</span>@endif
                                    @if ($topic->notes)<span class="flex items-center gap-1"><x-icon name="pencil" class="size-3" />anotações</span>@endif
                                    @if ($topic->resource_url)<span class="flex items-center gap-1"><x-icon name="link" class="size-3" />material</span>@endif
                                </span>
                            @endif
                        </button>
                        @if ($organizing)
                            <button type="button" wire:click="move({{ $topic->id }}, -1)" class="tap rounded-lg p-2 hover:bg-sheet-2" aria-label="Subir"><x-icon name="arrow-up" class="size-4" /></button>
                            <button type="button" wire:click="move({{ $topic->id }}, 1)" class="tap rounded-lg p-2 hover:bg-sheet-2" aria-label="Descer"><x-icon name="arrow-down" class="size-4" /></button>
                        @else
                            {{-- Dificuldade: ● fácil · ●● médio · ●●● difícil (toque para trocar) --}}
                            <button type="button" wire:click="setDifficulty({{ $topic->id }}, {{ ($topic->difficulty ?? 0) % 3 + 1 }})"
                                    class="tap flex gap-0.5 rounded-lg p-2" title="Dificuldade: {{ \App\Models\Topic::DIFFICULTIES[$topic->difficulty] ?? 'não definida' }}"
                                    aria-label="Dificuldade: {{ \App\Models\Topic::DIFFICULTIES[$topic->difficulty] ?? 'não definida' }}">
                                @foreach ([1, 2, 3] as $level)
                                    <span @class(['size-2 rounded-full',
                                                  'bg-rule' => ($topic->difficulty ?? 0) < $level,
                                                  'bg-done' => $topic->difficulty === 1 && $level <= 1,
                                                  'bg-marker' => $topic->difficulty === 2 && $level <= 2,
                                                  'bg-late' => $topic->difficulty === 3])></span>
                                @endforeach
                            </button>
                        @endif
                    </div>
                @endforeach
            </x-ui.card>
        @endif

        <form wire:submit="addTopic" class="flex gap-2">
            <input type="text" wire:model="newTopic" placeholder="Adicionar tópico e Enter" maxlength="200" aria-label="Novo tópico"
                   class="min-w-0 flex-1 rounded-xl border-2 border-dashed border-rule bg-sheet px-4 py-3 outline-none placeholder:text-ink-soft/70 focus:border-solid focus:border-pen">
            <button type="submit" class="tap grid size-[52px] shrink-0 place-items-center rounded-xl bg-pen text-white dark:text-[#14161c]" aria-label="Adicionar"><x-icon name="plus" /></button>
        </form>
        @error('newTopic') <p class="text-sm text-late">{{ $message }}</p> @enderror
    </section>

    {{-- Avaliações, entregas e média --}}
    <section class="mt-8 space-y-3">
        <div class="flex items-end justify-between gap-3">
            <h2 class="text-lg font-bold"><span class="highlight">Avaliações e prazos</span></h2>
            <button type="button" @click="$dispatch('edit-agenda', { subjectId: {{ $subject->id }} })" class="tap flex items-center gap-1 text-sm font-semibold text-pen">
                <x-icon name="plus" class="size-4" /> Adicionar
            </button>
        </div>
        @if ($agendaItems->isEmpty())
            <p class="rounded-2xl bg-sheet-2 px-4 py-4 text-sm text-ink-soft">Nenhuma prova, trabalho ou entrega cadastrada.</p>
        @else
            @if ($grades['average'] !== null || $grades['needed'] !== null)
                @php
                    $tone = ['approved' => 'bg-done/15 text-done', 'on_track' => 'bg-pen-soft text-pen', 'at_risk' => 'bg-marker/30 text-marker-ink', 'impossible' => 'bg-late/10 text-late'][$grades['status']] ?? 'bg-sheet-2 text-ink';
                    $fmt = fn ($v) => rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
                @endphp
                <div class="flex flex-wrap items-center gap-3 rounded-2xl border border-rule bg-sheet p-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Média atual</p>
                        <p class="font-display text-3xl font-bold">{{ $grades['average'] !== null ? $fmt($grades['average']) : '—' }}</p>
                    </div>
                    @if ($subject->passing_grade !== null)
                        <div class="ml-auto rounded-xl px-3 py-2 text-sm font-semibold {{ $tone }}">
                            @switch($grades['status'])
                                @case('approved') Aprovado(a)! Média mínima {{ $fmt($subject->passing_grade) }} garantida. @break
                                @case('impossible') Não dá mais para chegar a {{ $fmt($subject->passing_grade) }} nas avaliações restantes. @break
                                @default
                                    @if ($grades['needed'] !== null)
                                        Precisa de {{ $fmt($grades['needed']) }} (de 10) em média no que falta para fechar {{ $fmt($subject->passing_grade) }}.
                                    @else
                                        Abaixo da média mínima ({{ $fmt($subject->passing_grade) }}).
                                    @endif
                            @endswitch
                        </div>
                    @else
                        <p class="ml-auto text-xs text-ink-soft">Defina a média para aprovação na edição da disciplina.</p>
                    @endif
                </div>
            @endif
            <x-ui.card class="divide-y divide-rule">
                @foreach ($agendaItems as $item) <x-ui.agenda-row :item="$item" :toggle="false" :show-course="false" /> @endforeach
            </x-ui.card>
        @endif
    </section>

    {{-- Sessões --}}
    <section class="mt-8 space-y-3">
        <div class="flex items-end justify-between gap-3">
            <h2 class="text-lg font-bold"><span class="highlight">Sessões</span></h2>
            <button type="button" @click="$dispatch('log-session', { subjectId: {{ $subject->id }} })" class="tap flex items-center gap-1 text-sm font-semibold text-pen">
                <x-icon name="plus" class="size-4" /> Registrar
            </button>
        </div>
        @if ($sessionCount === 0)
            <p class="rounded-2xl bg-sheet-2 px-4 py-4 text-sm text-ink-soft">Nenhum estudo registrado ainda. Dê o play quando começar!</p>
        @else
            <p class="text-sm text-ink-soft"><strong class="font-display text-base text-ink">{{ \App\Support\Duration::human($sessionTotal) }}</strong> em {{ $sessionCount }} {{ $sessionCount === 1 ? 'sessão' : 'sessões' }}</p>
            <x-ui.card class="divide-y divide-rule">
                @foreach ($recentSessions as $s)
                    <button type="button" wire:key="ss-{{ $s->id }}" @click="$dispatch('log-session', { id: {{ $s->id }} })" class="tap flex w-full items-center gap-3 px-4 py-3 text-left hover:bg-sheet-2">
                        <x-icon :name="$s->kindIcon()" class="text-ink-soft" />
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold">{{ $s->topic?->title ?? $s->kindLabel() }}</span>
                            <span class="block text-xs text-ink-soft">{{ $s->date->isToday() ? 'Hoje' : ($s->date->isYesterday() ? 'Ontem' : $s->date->format('d/m')) }}{{ $s->started_at ? ' às '.$s->started_at->format('H:i') : '' }}</span>
                        </span>
                        <span class="font-display font-bold">{{ \App\Support\Duration::human($s->duration_seconds) }}</span>
                    </button>
                @endforeach
            </x-ui.card>
        @endif
    </section>

    <div class="mt-10 border-t border-rule pt-6">
        <button type="button" @click="$dispatch('open-sheet', 'delete-subject')" class="tap flex items-center gap-2 text-sm font-semibold text-late">
            <x-icon name="trash" class="size-4" /> {{ $isDisc ? 'Excluir disciplina' : 'Excluir módulo' }}
        </button>
    </div>

    <x-ui.sheet name="delete-subject" :title="$isDisc ? 'Excluir disciplina' : 'Excluir módulo'">
        <div class="space-y-4">
            <p class="text-sm text-ink-soft"><strong class="text-ink">{{ $subject->name }}</strong> e seus <strong class="text-ink">{{ $stats['total'] }} tópicos</strong> serão apagados. Isso não pode ser desfeito.</p>
            <p class="text-sm text-ink-soft">Para tirar da lista sem perder nada, edite e mude a situação para <strong class="text-ink">Arquivada</strong>.</p>
            <x-ui.button type="button" wire:click="deleteSubject" variant="danger" class="w-full" icon="trash">Excluir</x-ui.button>
        </div>
    </x-ui.sheet>

    <x-ui.sheet name="topic-form" title="Tópico">
        <form wire:submit="saveTopic" class="space-y-5">
            <x-ui.textarea label="Título" name="topicTitle" wire:model="topicTitle" rows="2" maxlength="200" />
            <div>
                <p class="mb-1.5 text-sm font-semibold">Situação</p>
                <div class="grid grid-cols-3 gap-2">
                    @foreach (\App\Models\Topic::STATUSES as $key => $label)
                        <label class="tap cursor-pointer rounded-xl border-2 border-rule px-2 py-2 text-center text-sm font-semibold has-[:checked]:border-pen has-[:checked]:bg-pen-soft has-[:checked]:text-pen">
                            <input type="radio" value="{{ $key }}" wire:model="topicStatus" class="sr-only">{{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div>
                <p class="mb-1.5 text-sm font-semibold">Dificuldade</p>
                <div class="grid grid-cols-4 gap-2">
                    <label class="tap cursor-pointer rounded-xl border-2 border-rule px-2 py-2 text-center text-sm font-semibold has-[:checked]:border-pen has-[:checked]:bg-pen-soft has-[:checked]:text-pen">
                        <input type="radio" value="" wire:model="topicDifficulty" class="sr-only">—
                    </label>
                    @foreach (\App\Models\Topic::DIFFICULTIES as $key => $label)
                        <label class="tap cursor-pointer rounded-xl border-2 border-rule px-2 py-2 text-center text-sm font-semibold has-[:checked]:border-pen has-[:checked]:bg-pen-soft has-[:checked]:text-pen">
                            <input type="radio" value="{{ $key }}" wire:model="topicDifficulty" class="sr-only">{{ $label }}
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="grid grid-cols-[8rem_1fr] gap-3">
                <x-ui.input label="Estimativa" name="topicEstimated" type="number" inputmode="numeric" min="1" wire:model="topicEstimated" suffix="min" />
                <x-ui.input label="Link do material" name="topicUrl" type="url" inputmode="url" wire:model="topicUrl" placeholder="https://" />
            </div>
            <x-ui.textarea label="Anotações" name="topicNotes" wire:model="topicNotes" rows="5" placeholder="Resumo, dúvidas, fórmulas, páginas..." />
            @if ($topicId)
                <div class="rounded-2xl bg-sheet-2 p-3">
                    <p class="flex items-center gap-2 text-sm font-semibold"><x-icon name="review" class="size-4" />
                        @if ($topicReview) Próxima revisão: {{ $topicReview->due_on->isToday() ? 'hoje' : $topicReview->due_on->translatedFormat('j \d\e M') }} @else Sem revisão agendada @endif
                    </p>
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (['today' => 'Hoje', 'tomorrow' => 'Amanhã', 'week' => 'Em 1 semana'] as $k => $l)
                            <button type="button" wire:click="reviewTopicOn('{{ $k }}')" class="tap rounded-full bg-sheet px-3 py-1 text-xs font-semibold text-pen">{{ $topicReview ? 'Mover p/ ' : 'Revisar ' }}{{ mb_strtolower($l) }}</button>
                        @endforeach
                    </div>
                </div>
                <x-ui.button :href="route('study', ['topico' => $topicId])" variant="marker" icon="play" class="w-full">Estudar este tópico</x-ui.button>
            @endif
            <div class="flex gap-2">
                <x-ui.button type="button" variant="secondary" icon="trash" wire:click="deleteTopic" wire:confirm="Excluir este tópico?" class="!text-late" aria-label="Excluir tópico" />
                <x-ui.button class="flex-1" icon="check">Salvar</x-ui.button>
            </div>
        </form>
    </x-ui.sheet>
</div>
