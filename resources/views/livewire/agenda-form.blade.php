<div>
    <x-ui.sheet name="agenda-form" :title="$itemId ? 'Editar' : 'Novo na agenda'">
        <form wire:submit="save" class="space-y-5">
            <div class="grid grid-cols-3 gap-2">
                @foreach (\App\Models\AgendaItem::TYPES as $key => [$label, $icon])
                    <label class="tap flex cursor-pointer flex-col items-center gap-1 rounded-xl border-2 border-rule px-2 py-2 text-center text-xs font-semibold has-[:checked]:border-pen has-[:checked]:bg-pen-soft has-[:checked]:text-pen">
                        <input type="radio" value="{{ $key }}" wire:model="type" class="sr-only"><x-icon :name="$icon" class="size-5" />{{ $label }}
                    </label>
                @endforeach
            </div>

            <x-ui.input label="Título" name="title" wire:model="title" placeholder="Ex.: P2, Seminário de Ética, Entrega do TCC" maxlength="150" />

            <div class="grid grid-cols-2 gap-3">
                <x-ui.select label="Curso" name="courseId" wire:model.live="courseId" :options="$courses->pluck('name', 'id')->all()" />
                <x-ui.select label="Disciplina / módulo" name="subjectId" wire:model="subjectId" :options="$subjects->pluck('name', 'id')->all()" placeholder="—" />
            </div>

            <div class="grid grid-cols-2 gap-3">
                <x-ui.input label="Data" name="dueOn" type="date" wire:model="dueOn" />
                <x-ui.input label="Horário" name="dueTime" type="time" wire:model="dueTime" hint="Opcional" />
            </div>

            <div class="rounded-2xl bg-sheet-2 p-3">
                <p class="mb-2 text-sm font-semibold">Nota <span class="font-normal text-ink-soft">(quando sair)</span></p>
                <div class="grid grid-cols-3 gap-3">
                    <x-ui.input label="Tirei" name="grade" type="number" step="0.01" min="0" inputmode="decimal" wire:model="grade" />
                    <x-ui.input label="Valia" name="maxGrade" type="number" step="0.01" min="0" inputmode="decimal" wire:model="maxGrade" />
                    <x-ui.input label="Peso" name="weight" type="number" step="0.1" min="0.1" inputmode="decimal" wire:model="weight" />
                </div>
            </div>

            <label class="flex cursor-pointer items-center gap-3 rounded-xl bg-sheet-2 px-4 py-3">
                <input type="checkbox" wire:model="done" class="size-5 accent-[#2f855a]">
                <span class="text-sm font-semibold">Já fiz / entreguei</span>
            </label>

            <x-ui.textarea label="Anotações" name="notes" wire:model="notes" rows="2" placeholder="Conteúdo, sala, formato de entrega..." />

            <div class="flex gap-2">
                @if ($itemId)
                    <x-ui.button type="button" variant="secondary" icon="trash" wire:click="delete" wire:confirm="Remover da agenda?" class="!text-late" aria-label="Excluir" />
                @endif
                <x-ui.button class="flex-1" icon="check">Salvar</x-ui.button>
            </div>
        </form>
    </x-ui.sheet>
</div>
