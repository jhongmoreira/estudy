@php $color = $timer?->subject->color ?? $timer?->subject->course->color ?? '#3047c9'; @endphp
<div class="flex flex-1 flex-col py-6"
     @keydown.window.space="if (! ['INPUT', 'TEXTAREA', 'SELECT', 'BUTTON'].includes($event.target.tagName)) { $event.preventDefault(); paused ? $wire.resume() : $wire.pause() }"
     x-data="timerClock($wire, @js($pomodoroConfig), {{ $timer?->pomodoro ? 'true' : 'false' }}, {{ $todaySeconds }}, {{ $goalSeconds }}, @js($timer?->subject->name ?? ''))">
    @if ($timer)
        {{-- Topo --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('home') }}" wire:navigate class="tap -ml-2 rounded-full p-2 text-ink-soft hover:bg-sheet-2" aria-label="Sair do modo foco (o cronômetro continua)">
                <x-icon name="chevron-left" class="size-6" />
            </a>
            <div class="min-w-0 flex-1 text-center">
                <p class="truncate text-sm text-ink-soft">{{ $timer->subject->course->name }}</p>
                <p class="truncate font-display text-lg font-bold">{{ $timer->subject->name }}</p>
            </div>
            <span class="size-10"></span>
        </div>
        @if ($timer->topic)
            <p class="mt-1 truncate text-center text-sm text-ink-soft">{{ $timer->topic->title }}</p>
        @endif

        {{-- Relógio --}}
        <div class="my-auto flex flex-col items-center py-8">
            <div class="relative grid size-72 place-items-center sm:size-80">
                <svg viewBox="0 0 200 200" class="absolute inset-0 -rotate-90">
                    <circle cx="100" cy="100" r="92" fill="none" stroke="var(--rule)" stroke-width="6" />
                    <circle cx="100" cy="100" r="92" fill="none" :stroke="ringDone ? 'var(--marker)' : '{{ $color }}'" stroke-width="6" stroke-linecap="round"
                            stroke-dasharray="578.05" :stroke-dashoffset="578.05 * (1 - ring)" class="transition-[stroke-dashoffset] duration-500" />
                </svg>
                <div class="relative text-center">
                    <p class="text-xs font-semibold uppercase tracking-widest" :class="paused ? 'text-late' : 'text-ink-soft'" x-text="label"></p>
                    <p class="font-display text-6xl font-bold tabular-nums sm:text-7xl" x-text="display">{{ \App\Support\Duration::clock($timer->elapsedSeconds()) }}</p>
                    <p class="mt-1 text-sm text-ink-soft" x-text="sub"></p>
                </div>
            </div>

            <div class="mt-8 flex items-center gap-4">
                <button type="button" x-show="!paused" wire:click="pause" class="tap grid size-16 place-items-center rounded-full border-2 border-rule bg-sheet" aria-label="Pausar">
                    <x-icon name="pause" class="size-7" />
                </button>
                <button type="button" x-show="paused" x-cloak wire:click="resume" class="tap grid size-16 place-items-center rounded-full bg-pen text-white dark:text-[#14161c]" aria-label="Retomar">
                    <x-icon name="play" class="size-7 translate-x-0.5" />
                </button>
                <button type="button" wire:click="finish" class="tap flex h-16 items-center gap-2 rounded-full bg-ink px-7 font-semibold text-paper" aria-label="Encerrar">
                    <x-icon name="stop" class="size-5" /> Encerrar
                </button>
            </div>
            <p class="mt-4 text-center text-xs text-ink-soft">Pode sair desta tela ou fechar o app: o tempo continua contando.<span class="hidden lg:inline"> Barra de espaço pausa/retoma.</span></p>
        </div>

        {{-- Anotações rápidas --}}
        <details class="rounded-2xl border border-rule bg-sheet" @if ($notes) open @endif>
            <summary class="flex cursor-pointer list-none items-center gap-2 px-4 py-3 text-sm font-semibold"><x-icon name="pencil" class="size-4" /> Anotações da sessão</summary>
            <textarea wire:model.live.debounce.1000ms="notes" rows="4" placeholder="Dúvidas, páginas, ideias..." aria-label="Anotações"
                      class="ruled block w-full resize-none border-t border-rule bg-transparent px-4 py-3 leading-7 outline-none"></textarea>
        </details>

        {{-- Folha "Como foi?" --}}
        <x-ui.sheet name="finish" title="Como foi?">
            <form wire:submit="save" class="space-y-5">
                @if ($forgotten)
                    <div class="flex gap-3 rounded-2xl bg-marker/30 p-3 text-sm text-marker-ink">
                        <x-icon name="alert" class="mt-0.5 size-5" />
                        <p>O cronômetro ficou ligado por mais de 4 horas. <strong>Esqueceu ligado?</strong> Ajuste a duração antes de salvar.</p>
                    </div>
                @endif

                <x-ui.input label="Tempo estudado (sem pausas)" name="duration" wire:model="duration" hint="Ex.: 45 min, 1h30" />

                <div>
                    <p class="mb-1.5 text-sm font-semibold">Tipo de estudo</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach (\App\Models\StudySession::KINDS as $key => [$label, $icon])
                            <label class="tap flex cursor-pointer items-center gap-1.5 rounded-full border-2 border-rule px-3 py-1.5 text-sm font-semibold has-[:checked]:border-pen has-[:checked]:bg-pen-soft has-[:checked]:text-pen">
                                <input type="radio" value="{{ $key }}" wire:model.live="kind" class="sr-only"><x-icon :name="$icon" class="size-4" /> {{ $label }}
                            </label>
                        @endforeach
                    </div>
                </div>

                @if ($topics->isNotEmpty())
                    <x-ui.select label="Tópico" name="topicId" wire:model.live="topicId" :options="$topics->pluck('title', 'id')->all()" placeholder="Geral (sem tópico)" />
                    @if ($topicId)
                        <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-sheet-2 px-4 py-3">
                            <input type="checkbox" wire:model="markDone" class="size-5 accent-[#2f855a]">
                            <span class="text-sm font-semibold">Terminei este tópico</span>
                        </label>
                    @endif
                @endif

                @if ($kind === 'review' && $pendingReview)
                    <div>
                        <p class="mb-1.5 text-sm font-semibold">Como foi a revisão?</p>
                        <div class="grid grid-cols-3 gap-2">
                            @foreach (\App\Models\Review::RATINGS as $key => [$label, $emoji])
                                <label class="tap flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 border-rule px-2 py-2 text-center text-xs font-semibold has-[:checked]:border-pen has-[:checked]:bg-pen-soft has-[:checked]:text-pen">
                                    <input type="radio" value="{{ $key }}" wire:model="reviewRating" class="sr-only"><span class="text-xl">{{ $emoji }}</span>{{ $label }}
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-3 gap-3">
                    <x-ui.input label="Páginas" name="pages" type="number" inputmode="numeric" min="0" wire:model="pages" />
                    <x-ui.input label="Questões" name="questionsTotal" type="number" inputmode="numeric" min="0" wire:model="questionsTotal" />
                    <x-ui.input label="Acertos" name="questionsCorrect" type="number" inputmode="numeric" min="0" wire:model="questionsCorrect" />
                </div>

                <x-ui.button class="w-full" icon="check">Salvar sessão</x-ui.button>
                <div class="flex items-center justify-between">
                    <button type="button" wire:click="resume" class="text-sm font-semibold text-pen">Voltar a estudar</button>
                    <button type="button" wire:click="discard" wire:confirm="Descartar este tempo? Nada será salvo." class="text-sm font-semibold text-late">Descartar</button>
                </div>
            </form>
        </x-ui.sheet>
    @endif
</div>
