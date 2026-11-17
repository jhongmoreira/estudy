@php
    $unit = $course->unitLabel();
    $units = $course->unitLabel(true);
    $isDisc = $unit === 'Disciplina';
@endphp
<div>
    <x-ui.page-header :title="$course->name" :back="route('courses')" :subtitle="$course->typeLabel().($course->institution ? ' · '.$course->institution : '')">
        <button type="button" x-data @click="$dispatch('edit-course', { id: {{ $course->id }} })" class="tap rounded-full p-2.5 text-ink-soft hover:bg-sheet-2" aria-label="Editar curso">
            <x-icon name="pencil" />
        </button>
    </x-ui.page-header>

    {{-- Resumo --}}
    <div class="relative overflow-hidden rounded-2xl border border-rule bg-sheet p-5">
        <span class="absolute inset-x-0 top-0 h-1.5" style="background: {{ $course->color }}"></span>
        <div class="flex items-center gap-5">
            <x-ui.ring :value="$courseStats['progress']" :max="1" :size="96" :stroke="9" :color="$course->color">
                <span class="font-display text-xl font-bold">{{ (int) floor($courseStats['progress'] * 100) }}%</span>
            </x-ui.ring>
            <dl class="grid flex-1 grid-cols-2 gap-x-4 gap-y-2 text-sm">
                <div><dt class="text-ink-soft">{{ $units }}</dt><dd class="font-display text-lg font-bold">{{ $courseStats['subjects'] }}</dd></div>
                <div><dt class="text-ink-soft">Tópicos</dt><dd class="font-display text-lg font-bold">{{ $courseStats['done'] }}<span class="text-sm font-medium text-ink-soft">/{{ $courseStats['total'] }}</span></dd></div>
                <div><dt class="text-ink-soft">Situação</dt><dd class="font-semibold">{{ $course->statusLabel() }}</dd></div>
                @if ($course->workload_hours)
                    <div><dt class="text-ink-soft">Carga horária</dt><dd class="font-semibold">{{ $course->workload_hours }} h</dd></div>
                @elseif ($course->target_on)
                    <div><dt class="text-ink-soft">Previsão</dt><dd class="font-semibold">{{ $course->target_on->translatedFormat('M/Y') }}</dd></div>
                @endif
            </dl>
        </div>
        <div class="mt-4 flex flex-wrap gap-2">
            <x-ui.button :href="route('study', ['curso' => $course->id])" icon="play" class="flex-1 !py-2.5">Estudar</x-ui.button>
            @if ($course->url)
                <x-ui.button :href="$course->url" :navigate="false" variant="secondary" icon="link" class="!py-2.5" target="_blank" rel="noopener">Abrir</x-ui.button>
            @endif
        </div>
        @if ($course->notes)
            <p class="mt-4 whitespace-pre-line border-t border-rule pt-3 text-sm text-ink-soft">{{ $course->notes }}</p>
        @endif
    </div>

    {{-- Disciplinas / módulos --}}
    <section class="mt-8 space-y-3" x-data>
        <div class="flex items-end justify-between gap-3">
            <h2 class="text-lg font-bold"><span class="highlight">{{ $units }}</span></h2>
            <div class="flex items-center gap-3">
                @if ($subjects->count() > 1)
                    <button type="button" wire:click="$toggle('organizing')" class="tap flex items-center gap-1 text-sm font-semibold text-pen">
                        <x-icon :name="$organizing ? 'check' : 'arrows'" class="size-4" /> {{ $organizing ? 'Concluir' : 'Organizar' }}
                    </button>
                @endif
                <button type="button" @click="$dispatch('paste-list', { courseId: {{ $course->id }} })" class="tap flex items-center gap-1 text-sm font-semibold text-pen">
                    <x-icon name="clipboard" class="size-4" /> Colar lista
                </button>
            </div>
        </div>

        @if ($subjects->isEmpty())
            <x-ui.empty icon="list" :title="$isDisc ? 'Adicione as disciplinas' : 'Adicione os módulos'"
                        :text="$isDisc ? 'Uma a uma ou colando a grade do semestre (com os conteúdos de cada disciplina).' : 'Um a um ou colando o sumário/grade do curso — cada linha vira um tópico.'">
                <div class="flex flex-wrap justify-center gap-2">
                    <x-ui.button type="button" icon="plus" @click="$dispatch('edit-subject', { courseId: {{ $course->id }}, id: null })">{{ $isDisc ? 'Nova disciplina' : 'Novo módulo' }}</x-ui.button>
                    <x-ui.button type="button" variant="secondary" icon="clipboard" @click="$dispatch('paste-list', { courseId: {{ $course->id }} })">Colar lista</x-ui.button>
                </div>
            </x-ui.empty>
        @else
            @foreach ($groups as $term => $items)
                @if ($term !== '' || $groups->count() > 1)
                    <p class="pt-2 text-sm font-semibold uppercase tracking-wide text-ink-soft">{{ $term !== '' ? $term : 'Sem período' }}</p>
                @endif
                <x-ui.card class="divide-y divide-rule">
                    @foreach ($items as $subject)
                        @php $s = $stats[$subject->id]; $c = $subject->color ?? $course->color; @endphp
                        <div class="flex items-center gap-2 pr-2" wire:key="subject-{{ $subject->id }}">
                            <a href="{{ route('subjects.show', $subject) }}" wire:navigate class="tap flex min-w-0 flex-1 items-center gap-3 px-4 py-3.5 hover:bg-sheet-2">
                                <span class="h-10 w-1.5 shrink-0 rounded-full" style="background: {{ $c }}"></span>
                                <span class="min-w-0 flex-1">
                                    <span @class(['block truncate font-semibold', 'text-ink-soft line-through' => $subject->status !== 'active'])>{{ $subject->name }}</span>
                                    <span class="mt-1.5 flex items-center gap-2">
                                        <x-ui.progress :value="$s['progress']" :max="1" :color="$c" class="max-w-40" />
                                        <span class="shrink-0 text-xs text-ink-soft">{{ $s['total'] > 0 ? $s['done'].'/'.$s['total'] : 'sem tópicos' }}</span>
                                    </span>
                                </span>
                                @unless ($organizing)<x-icon name="chevron-right" class="text-ink-soft" />@endunless
                            </a>
                            @if ($organizing)
                                <button type="button" wire:click="move({{ $subject->id }}, -1)" class="tap rounded-lg p-2 hover:bg-sheet-2" aria-label="Subir"><x-icon name="arrow-up" class="size-4" /></button>
                                <button type="button" wire:click="move({{ $subject->id }}, 1)" class="tap rounded-lg p-2 hover:bg-sheet-2" aria-label="Descer"><x-icon name="arrow-down" class="size-4" /></button>
                            @endif
                        </div>
                    @endforeach
                </x-ui.card>
            @endforeach
            <button type="button" @click="$dispatch('edit-subject', { courseId: {{ $course->id }}, id: null })"
                    class="tap flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-rule py-3 font-semibold text-pen hover:bg-sheet">
                <x-icon name="plus" /> {{ $isDisc ? 'Nova disciplina' : 'Novo módulo' }}
            </button>
        @endif
    </section>

    <section class="mt-8 space-y-3" x-data>
        <div class="flex items-end justify-between gap-3">
            <h2 class="text-lg font-bold"><span class="highlight">Agenda</span></h2>
            <button type="button" @click="$dispatch('edit-agenda', { courseId: {{ $course->id }} })" class="tap flex items-center gap-1 text-sm font-semibold text-pen">
                <x-icon name="plus" class="size-4" /> Adicionar
            </button>
        </div>
        @if ($agendaItems->isEmpty())
            <p class="rounded-2xl bg-sheet-2 px-4 py-4 text-sm text-ink-soft">Sem provas, trabalhos ou entregas por perto.</p>
        @else
            <x-ui.card class="divide-y divide-rule">
                @foreach ($agendaItems as $item) <x-ui.agenda-row :item="$item" :toggle="false" :show-course="false" /> @endforeach
            </x-ui.card>
        @endif
    </section>

    <div class="mt-8 flex flex-wrap gap-2">
        <x-ui.button :href="route('reports.course', $course)" :navigate="false" variant="secondary" icon="download" class="!py-2.5">Progresso em PDF</x-ui.button>
        @if ($course->isAcademic() || $agendaItems->isNotEmpty())
            <x-ui.button :href="route('reports.grades', $course)" :navigate="false" variant="secondary" icon="download" class="!py-2.5">Boletim em PDF</x-ui.button>
        @endif
    </div>

    {{-- Zona de perigo --}}
    <div class="mt-10 border-t border-rule pt-6" x-data>
        <button type="button" @click="$dispatch('open-sheet', 'delete-course')" class="tap flex items-center gap-2 text-sm font-semibold text-late">
            <x-icon name="trash" class="size-4" /> Excluir curso
        </button>
    </div>

    <x-ui.sheet name="delete-course" title="Excluir curso">
        <form wire:submit="deleteCourse" class="space-y-4">
            <p class="text-sm text-ink-soft">
                Serão apagados <strong class="text-ink">{{ $courseStats['subjects'] }} {{ mb_strtolower($courseStats['subjects'] === 1 ? $unit : $units) }}</strong>
                e <strong class="text-ink">{{ $courseStats['total'] }} tópicos</strong>. Isso não pode ser desfeito.
            </p>
            <p class="text-sm text-ink-soft">Se você terminou ou desistiu, prefira mudar a situação para <strong class="text-ink">Concluído</strong> ou <strong class="text-ink">Abandonado</strong> — o histórico continua.</p>
            <x-ui.input label="Digite o nome do curso para confirmar" name="confirmName" wire:model="confirmName" :placeholder="$course->name" autocomplete="off" />
            <x-ui.button variant="danger" class="w-full" icon="trash">Excluir definitivamente</x-ui.button>
        </form>
    </x-ui.sheet>
</div>
