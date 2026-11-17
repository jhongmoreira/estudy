<div x-data>
    <x-ui.page-header title="Estudar" subtitle="Escolha o que estudar e comece o cronômetro">
        <button type="button" @click="$dispatch('log-session', { subjectId: {{ $subjectId ?? 'null' }} })" class="tap flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-pen hover:bg-pen-soft">
            <x-icon name="pencil" class="size-4" /> Registrar manual
        </button>
    </x-ui.page-header>

    @if ($courses->isEmpty() && ! $subject)
        <x-ui.empty icon="cap" title="Primeiro, cadastre o que você estuda" text="Crie um curso com suas disciplinas ou módulos — depois é só voltar aqui e dar play.">
            <x-ui.button :href="route('courses', ['novo' => 1])" icon="plus">Novo curso</x-ui.button>
        </x-ui.empty>
    @elseif (! $subject)
        {{-- Passo 1: escolher a disciplina/módulo --}}
        @if ($cycleItem && ! $courseId)
            <button type="button" wire:click="chooseSubject({{ $cycleItem->subject_id }})"
                    class="tap mb-3 flex w-full items-center gap-4 rounded-2xl border-2 border-marker bg-marker/20 p-4 text-left">
                <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-marker text-[#1f2a44]"><x-icon name="cycle" class="size-6" /></span>
                <span class="min-w-0">
                    <span class="block text-xs font-semibold uppercase tracking-wide text-marker-ink">Próximo do ciclo · faltam {{ \App\Support\Duration::human($cycleItem->remainingSeconds()) }}</span>
                    <span class="block truncate font-display text-lg font-bold">{{ $cycleItem->subject->name }}</span>
                    <span class="block truncate text-sm text-ink-soft">{{ $cycleItem->subject->course->name }}</span>
                </span>
            </button>
        @endif
        @if ($last && ! $courseId)
            <button type="button" wire:click="continueLast({{ $last->subject_id }}, {{ $last->topic_id && $last->topic?->status !== 'done' ? $last->topic_id : 'null' }})"
                    class="tap mb-6 flex w-full items-center gap-4 rounded-2xl border-2 border-dashed border-pen/40 bg-pen-soft/50 p-4 text-left">
                <span class="grid size-12 shrink-0 place-items-center rounded-xl bg-pen text-white dark:text-[#14161c]"><x-icon name="play" class="size-6" /></span>
                <span class="min-w-0">
                    <span class="block text-xs font-semibold uppercase tracking-wide text-pen">Continuar de onde parou</span>
                    <span class="block truncate font-display text-lg font-bold">{{ $last->subject->name }}</span>
                    <span class="block truncate text-sm text-ink-soft">{{ $last->subject->course->name }}@if ($last->topic && $last->topic->status !== 'done') · {{ $last->topic->title }}@endif</span>
                </span>
            </button>
        @endif

        <div class="space-y-6">
            @foreach ($courses as $course)
                <section wire:key="study-course-{{ $course->id }}">
                    <h2 class="mb-2 flex items-center gap-2 text-sm font-semibold uppercase tracking-wide text-ink-soft">
                        <span class="size-2.5 rounded-full" style="background: {{ $course->color }}"></span> {{ $course->name }}
                    </h2>
                    @if ($course->subjects->isEmpty())
                        <a href="{{ route('courses.show', $course) }}" wire:navigate class="block rounded-2xl border border-dashed border-rule px-4 py-3 text-sm text-ink-soft">
                            Sem {{ mb_strtolower($course->unitLabel(true)) }} ainda — <span class="font-semibold text-pen">adicionar</span>
                        </a>
                    @else
                        <div class="grid gap-2 sm:grid-cols-2">
                            @foreach ($course->subjects as $item)
                                <button type="button" wire:click="chooseSubject({{ $item->id }})"
                                        class="tap flex min-w-0 items-center gap-3 rounded-2xl border border-rule bg-sheet px-4 py-3 text-left hover:bg-sheet-2">
                                    <span class="h-8 w-1.5 shrink-0 rounded-full" style="background: {{ $item->color ?? $course->color }}"></span>
                                    <span class="min-w-0 flex-1 truncate font-semibold">{{ $item->name }}</span>
                                    <x-icon name="chevron-right" class="text-ink-soft" />
                                </button>
                            @endforeach
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
        @error('subjectId') <p class="mt-3 text-sm text-late">{{ $message }}</p> @enderror
    @else
        {{-- Passo 2: tópico, tipo, Pomodoro e play --}}
        @php $color = $subject->color ?? $subject->course->color; @endphp
        <div class="relative overflow-hidden rounded-2xl border border-rule bg-sheet p-4 pl-5">
            <span class="absolute inset-y-0 left-0 w-1.5" style="background: {{ $color }}"></span>
            <div class="flex items-center gap-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm text-ink-soft">{{ $subject->course->name }}</p>
                    <p class="truncate font-display text-xl font-bold">{{ $subject->name }}</p>
                </div>
                <button type="button" wire:click="clearSubject" class="tap rounded-xl px-3 py-2 text-sm font-semibold text-pen hover:bg-pen-soft">Trocar</button>
            </div>
        </div>

        <div class="mt-6 space-y-6">
            <section>
                <h2 class="mb-2 text-sm font-semibold">Tópico <span class="font-normal text-ink-soft">(opcional)</span></h2>
                @if ($topics->isEmpty())
                    <p class="rounded-2xl bg-sheet-2 px-4 py-3 text-sm text-ink-soft">Nenhum tópico pendente. Você pode estudar a disciplina em geral.</p>
                @else
                    <div class="max-h-64 space-y-1.5 overflow-y-auto rounded-2xl border border-rule bg-sheet p-2">
                        <button type="button" wire:click="chooseTopic(null)"
                                @class(['tap flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm', 'bg-pen-soft font-semibold text-pen' => $topicId === null, 'hover:bg-sheet-2' => $topicId !== null])>
                            <x-icon name="book" class="size-4" /> Geral (sem tópico)
                        </button>
                        @foreach ($topics as $t)
                            <button type="button" wire:click="chooseTopic({{ $t->id }})" wire:key="st-{{ $t->id }}"
                                    @class(['tap flex w-full items-center gap-2 rounded-xl px-3 py-2 text-left text-sm', 'bg-pen-soft font-semibold text-pen' => $topicId === $t->id, 'hover:bg-sheet-2' => $topicId !== $t->id])>
                                <span @class(['size-2 shrink-0 rounded-full', 'bg-marker' => $t->status === 'doing', 'bg-rule' => $t->status !== 'doing'])></span>
                                <span class="min-w-0 flex-1 truncate">{{ $t->title }}</span>
                                @if ($t->estimated_min)<span class="shrink-0 text-xs text-ink-soft">{{ \App\Support\Duration::human($t->estimated_min * 60) }}</span>@endif
                            </button>
                        @endforeach
                    </div>
                @endif
            </section>

            <section>
                <h2 class="mb-2 text-sm font-semibold">Como vai estudar?</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach (\App\Models\StudySession::KINDS as $key => [$label, $icon])
                        <label class="tap flex cursor-pointer items-center gap-1.5 rounded-full border-2 border-rule px-3 py-1.5 text-sm font-semibold has-[:checked]:border-pen has-[:checked]:bg-pen-soft has-[:checked]:text-pen">
                            <input type="radio" value="{{ $key }}" wire:model="kind" class="sr-only"><x-icon :name="$icon" class="size-4" /> {{ $label }}
                        </label>
                    @endforeach
                </div>
            </section>

            <label class="flex cursor-pointer items-center gap-3 rounded-2xl border border-rule bg-sheet p-4">
                <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-marker/40 text-marker-ink"><x-icon name="clock" /></span>
                <span class="min-w-0 flex-1">
                    <span class="block font-semibold">Pomodoro</span>
                    <span class="block text-sm text-ink-soft">{{ $pomodoroConfig['focus'] }} min de foco, {{ $pomodoroConfig['short'] }} de pausa (longa de {{ $pomodoroConfig['long'] }} a cada {{ $pomodoroConfig['every'] }})</span>
                </span>
                <input type="checkbox" wire:model="pomodoro" class="peer sr-only">
                <span class="relative h-7 w-12 shrink-0 rounded-full bg-rule transition peer-checked:bg-pen after:absolute after:left-1 after:top-1 after:size-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
            </label>

            @error('timer') <p class="text-sm text-late">{{ $message }}</p> @enderror
            <div class="sticky bottom-[calc(5rem+env(safe-area-inset-bottom))] z-10 lg:bottom-6">
                <x-ui.button type="button" wire:click="start" icon="play" class="w-full !py-4 text-lg shadow-lg">Começar</x-ui.button>
            </div>
        </div>
    @endif
</div>
