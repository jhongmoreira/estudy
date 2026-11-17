<div class="flex flex-1 flex-col py-8">
    <div class="flex items-center justify-between">
        <img src="/icons/logo.svg" alt="" class="size-10 rounded-xl">
        <button type="button" wire:click="skip" class="text-sm font-semibold text-ink-soft hover:text-ink">Pular</button>
    </div>

    <div class="mt-6 flex gap-2" aria-hidden="true">
        @foreach (range(1, \App\Livewire\Onboarding::STEPS) as $s)
            <span @class(['h-1.5 flex-1 rounded-full', 'bg-pen' => $step >= $s, 'bg-rule' => $step < $s])></span>
        @endforeach
    </div>

    <p class="mt-10 text-sm font-semibold uppercase tracking-wide text-pen">Passo {{ $step }} de {{ \App\Livewire\Onboarding::STEPS }}</p>

    @if ($step === 1)
        <div class="flex-1">
            <h1 class="mt-2 text-3xl font-bold">O que você está <span class="highlight">estudando?</span></h1>
            <p class="mt-2 text-ink-soft">Cadastre um ou mais cursos. Depois você adiciona as disciplinas ou módulos e os tópicos.</p>

            <div class="mt-6 space-y-3">
                @foreach ($courses as $i => $item)
                    <div class="rounded-2xl border border-rule bg-sheet p-3" wire:key="onb-course-{{ $i }}">
                        <div class="flex gap-2">
                            <select wire:model="courses.{{ $i }}.type" aria-label="Tipo" class="w-40 shrink-0 rounded-xl border-2 border-rule bg-sheet px-2 py-2.5 text-sm font-semibold outline-none focus:border-pen">
                                @foreach (\App\Models\Course::TYPES as $key => [$label])
                                    <option value="{{ $key }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @if (count($courses) > 1)
                                <button type="button" wire:click="removeCourse({{ $i }})" class="tap ml-auto rounded-xl p-2 text-ink-soft hover:bg-sheet-2" aria-label="Remover"><x-icon name="x" /></button>
                            @endif
                        </div>
                        <input type="text" wire:model="courses.{{ $i }}.name" maxlength="120" aria-label="Nome do curso"
                               placeholder="{{ ['Ex.: Engenharia de Software', 'Ex.: Inglês avançado', 'Ex.: Livro “Hábitos Atômicos”'][$i % 3] }}"
                               class="mt-2 block w-full rounded-xl border-2 border-rule bg-sheet px-4 py-3 outline-none focus:border-pen">
                    </div>
                @endforeach
            </div>
            @if (count($courses) < 6)
                <button type="button" wire:click="addCourse" class="tap mt-3 flex items-center gap-2 text-sm font-semibold text-pen"><x-icon name="plus" class="size-4" /> Adicionar outro</button>
            @endif
        </div>

        <x-ui.button type="button" wire:click="next" class="mt-8 w-full">Continuar</x-ui.button>
    @elseif ($step === 2)
        <div class="flex-1">
            <h1 class="mt-2 text-3xl font-bold">Quanto você quer <span class="highlight">estudar por dia?</span></h1>
            <p class="mt-2 text-ink-soft">Uma meta realista vale mais que uma meta heroica. Dá para mudar quando quiser.</p>

            <div class="mt-8 grid grid-cols-2 gap-3">
                @foreach ([30 => 'Leve', 60 => 'Constante', 120 => 'Puxado', 180 => 'Intenso'] as $min => $label)
                    <button type="button" wire:click="setGoal({{ $min }})"
                            @class(['tap rounded-2xl border-2 p-4 text-left',
                                    'border-pen bg-pen-soft' => $daily_goal_min === $min,
                                    'border-rule bg-sheet' => $daily_goal_min !== $min])>
                        <span class="block font-display text-2xl font-bold">{{ \App\Support\Duration::human($min * 60) }}</span>
                        <span class="text-sm text-ink-soft">{{ $label }}</span>
                    </button>
                @endforeach
            </div>
            <div class="mt-4">
                <x-ui.input label="Ou outro valor" name="daily_goal_min" type="number" inputmode="numeric" min="5" max="720" wire:model.live.debounce.400ms="daily_goal_min" suffix="min" />
            </div>
        </div>

        <div class="mt-8 grid grid-cols-[auto_1fr] gap-3">
            <x-ui.button type="button" variant="secondary" wire:click="back" icon="chevron-left" aria-label="Voltar" />
            <x-ui.button type="button" wire:click="next">Continuar</x-ui.button>
        </div>
    @else
        <div class="flex-1">
            <h1 class="mt-2 text-3xl font-bold">Em quais dias <span class="highlight">você estuda?</span></h1>
            <p class="mt-2 text-ink-soft">Nos dias de folga não agendamos revisões e sua sequência não quebra.</p>

            <div class="mt-8">
                <x-ui.day-picker :selected="$study_days" />
                @error('study_days') <p class="mt-2 text-sm text-late">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="mt-8 grid grid-cols-[auto_1fr] gap-3">
            <x-ui.button type="button" variant="secondary" wire:click="back" icon="chevron-left" aria-label="Voltar" />
            <x-ui.button type="button" wire:click="finish">Começar a estudar</x-ui.button>
        </div>
    @endif
</div>
