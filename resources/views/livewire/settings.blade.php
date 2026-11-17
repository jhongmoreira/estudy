<div>
    <x-ui.page-header title="Configurações" :back="route('more')" subtitle="Ajuste o Estudy à sua rotina" />

    <form wire:submit="save" class="max-w-2xl space-y-6">
        <x-ui.section title="Metas">
            <x-ui.card class="space-y-4 p-4">
                <x-ui.input label="Meta diária" name="daily_goal_min" type="number" inputmode="numeric" min="5" max="720" wire:model="daily_goal_min" suffix="min"
                            hint="Quanto você quer estudar por dia (o anel da tela Hoje)." />
                <x-ui.input label="Mínimo para a sequência" name="streak_min" type="number" inputmode="numeric" min="1" wire:model="streak_min" suffix="min"
                            hint="Dias com pelo menos esse tempo contam para a sua sequência de dias seguidos." />
            </x-ui.card>
        </x-ui.section>

        <x-ui.section title="Dias de estudo">
            <x-ui.card class="space-y-3 p-4">
                <x-ui.day-picker :selected="$study_days" />
                @error('study_days') <p class="text-sm text-late">{{ $message }}</p> @enderror
                <p class="text-sm text-ink-soft">Nos outros dias, revisões não são agendadas e a sequência não quebra se você descansar.</p>
            </x-ui.card>
        </x-ui.section>

        <x-ui.section title="Semana">
            <x-ui.card class="p-4">
                <label class="mb-1.5 block text-sm font-semibold" for="week_starts_on">A semana começa no</label>
                <select id="week_starts_on" wire:model="week_starts_on" class="block w-full rounded-xl border-2 border-rule bg-sheet px-4 py-3 outline-none focus:border-pen">
                    <option value="1">Segunda-feira</option>
                    <option value="0">Domingo</option>
                </select>
            </x-ui.card>
        </x-ui.section>

        <x-ui.button class="w-full" icon="check">Salvar</x-ui.button>
    </form>
</div>
