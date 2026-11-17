<div>
    @php $isDisc = $unit === 'Disciplina'; @endphp
    <x-ui.sheet name="subject-form" :title="($subjectId ? 'Editar ' : ($isDisc ? 'Nova ' : 'Novo ')).mb_strtolower($unit)">
        <form wire:submit="save" class="space-y-5">
            <x-ui.input label="Nome" name="name" wire:model="name" :placeholder="$isDisc ? 'Ex.: Cálculo II' : 'Ex.: Módulo 1 — Fundamentos'" maxlength="120" />

            @if ($academic)
                <div class="grid grid-cols-2 gap-3">
                    <x-ui.input label="Período" name="term" wire:model="term" placeholder="Ex.: 2026.2" maxlength="20" hint="Agrupa as disciplinas" />
                    <x-ui.input label="Média p/ aprovação" name="passing_grade" type="number" step="0.1" min="0" max="10" inputmode="decimal" wire:model="passing_grade" placeholder="7,0" />
                </div>
            @endif

            <x-ui.color-picker wire:model="color" :nullable="true" />

            <div class="grid grid-cols-2 gap-3">
                <x-ui.select label="Situação" name="status" wire:model="status" :options="\App\Models\Subject::STATUSES" />
                <x-ui.input label="Meta semanal" name="weekly_goal_hours" type="number" inputmode="numeric" min="1" max="100" wire:model="weekly_goal_hours" suffix="h" />
            </div>

            <x-ui.textarea label="Anotações" name="notes" wire:model="notes" rows="3" :placeholder="$isDisc ? 'Professor, sala, horário, ementa...' : 'Observações'" />

            <x-ui.button class="w-full" icon="check">{{ $subjectId ? 'Salvar' : 'Criar' }}</x-ui.button>
        </form>
    </x-ui.sheet>
</div>
