<div>
    <x-ui.sheet name="course-form" :title="$courseId ? 'Editar curso' : 'Novo curso'">
        <form wire:submit="save" class="space-y-5">
            <div>
                <p class="mb-1.5 text-sm font-semibold">O que você vai estudar?</p>
                <div class="grid grid-cols-2 gap-2">
                    @foreach (\App\Models\Course::TYPES as $key => [$label, $icon])
                        <label class="tap flex cursor-pointer items-center gap-2 rounded-xl border-2 border-rule px-3 py-2.5 text-sm font-semibold has-[:checked]:border-pen has-[:checked]:bg-pen-soft has-[:checked]:text-pen">
                            <input type="radio" value="{{ $key }}" wire:model.live="type" class="sr-only">
                            <x-icon :name="$icon" class="size-4" /> {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('type') <p class="mt-1.5 text-sm text-late">{{ $message }}</p> @enderror
            </div>

            <x-ui.input label="Nome" name="name" wire:model="name" placeholder="Ex.: Análise e Desenvolvimento de Sistemas" maxlength="120" />
            <x-ui.input label="Instituição, plataforma ou autor" name="institution" wire:model="institution" placeholder="Ex.: UFMG, Alura, Coursera" maxlength="120" />

            <x-ui.color-picker wire:model="color" />

            {{-- Ícone do card: emoji opcional; vazio = ícone do tipo de curso --}}
            <div>
                <p class="text-sm font-semibold">Ícone <span class="font-normal text-ink-soft">(opcional)</span></p>
                <p class="mb-2 text-xs text-ink-soft">Aparece no card do curso. Em “Padrão”, usamos o ícone do tipo.</p>
                <div class="flex flex-wrap items-center gap-2" role="radiogroup" aria-label="Ícone">
                    <label class="tap flex h-10 cursor-pointer items-center gap-1.5 rounded-xl border-2 border-rule px-3 text-sm font-semibold text-ink-soft has-[:checked]:border-pen has-[:checked]:bg-pen-soft has-[:checked]:text-pen">
                        <input type="radio" value="" wire:model.live="emoji" class="sr-only">
                        <x-icon :name="\App\Models\Course::TYPES[$type][1] ?? 'book'" class="size-4" /> Padrão
                    </label>
                    @foreach (['📘', '🎓', '💻', '🗣️', '🧮', '🧪', '🧠', '🎨', '🎵', '📊', '⚖️', '🩺'] as $e)
                        <label class="tap grid size-10 cursor-pointer place-items-center rounded-xl border-2 border-rule text-xl has-[:checked]:border-pen has-[:checked]:bg-pen-soft">
                            <input type="radio" value="{{ $e }}" wire:model.live="emoji" class="sr-only" aria-label="{{ $e }}">{{ $e }}
                        </label>
                    @endforeach
                    <input type="text" wire:model.live.debounce.400ms="emoji" maxlength="16" aria-label="Outro emoji" placeholder="outro"
                           class="h-10 w-20 rounded-xl border-2 border-dashed border-rule bg-sheet px-2 text-center outline-none focus:border-pen">
                </div>
                @error('emoji') <p class="mt-1.5 text-sm text-late">{{ $message }}</p> @enderror
            </div>

            <x-ui.select label="Situação" name="status" wire:model.live="status" :options="\App\Models\Course::STATUSES" />

            <div class="grid grid-cols-2 gap-3">
                <x-ui.input label="Início" name="started_on" type="date" wire:model="started_on" />
                @if ($status === 'completed')
                    <x-ui.input label="Concluído em" name="completed_on" type="date" wire:model="completed_on" />
                @else
                    <x-ui.input label="Previsão de término" name="target_on" type="date" wire:model="target_on" />
                @endif
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-ui.input label="Carga horária" name="workload_hours" type="number" inputmode="numeric" min="1" wire:model="workload_hours" suffix="h" hint="A do certificado" />
                <x-ui.input label="Meta semanal" name="weekly_goal_hours" type="number" inputmode="numeric" min="1" max="100" wire:model="weekly_goal_hours" suffix="h" hint="Opcional" />
            </div>

            <x-ui.input label="Link" name="url" type="url" wire:model="url" placeholder="https://" inputmode="url" />
            <x-ui.textarea label="Anotações" name="notes" wire:model="notes" rows="3" />

            <x-ui.button class="w-full" icon="check">{{ $courseId ? 'Salvar' : 'Criar curso' }}</x-ui.button>
        </form>
    </x-ui.sheet>
</div>
