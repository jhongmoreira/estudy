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

        <x-ui.section title="Revisões">
            <x-ui.card class="space-y-4 p-4">
                <label class="flex cursor-pointer items-center gap-3">
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">Agendar revisões automaticamente</span>
                        <span class="block text-sm text-ink-soft">Ao estudar um tópico pela primeira vez.</span>
                    </span>
                    <input type="checkbox" wire:model="reviews_auto" class="peer sr-only">
                    <span class="relative h-7 w-12 shrink-0 rounded-full bg-rule transition peer-checked:bg-pen after:absolute after:left-1 after:top-1 after:size-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                </label>
                <div>
                    <x-ui.input label="Intervalos (dias depois de estudar)" name="review_intervals" wire:model="review_intervals" placeholder="1, 7, 30" />
                    <div class="mt-2 flex flex-wrap gap-2">
                        @foreach (['1, 7, 30', '1, 3, 7, 15, 30', '2, 7, 21, 60', '1, 7, 30, 90'] as $preset)
                            <button type="button" wire:click="usePreset('{{ $preset }}')" class="tap rounded-full bg-sheet-2 px-3 py-1 text-sm font-semibold text-ink-soft hover:text-ink">{{ $preset }}</button>
                        @endforeach
                    </div>
                </div>
                <x-ui.input label="Máximo por dia ao reagendar atrasadas" name="reviews_per_day" type="number" inputmode="numeric" min="1" wire:model="reviews_per_day" />
            </x-ui.card>
        </x-ui.section>

        <x-ui.section title="Pomodoro">
            <x-ui.card class="space-y-3 p-4">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    <x-ui.input label="Foco" name="pomodoro.focus" type="number" inputmode="numeric" wire:model="pomodoro.focus" suffix="min" />
                    <x-ui.input label="Pausa curta" name="pomodoro.short" type="number" inputmode="numeric" wire:model="pomodoro.short" suffix="min" />
                    <x-ui.input label="Pausa longa" name="pomodoro.long" type="number" inputmode="numeric" wire:model="pomodoro.long" suffix="min" />
                    <x-ui.input label="Longa a cada" name="pomodoro.every" type="number" inputmode="numeric" wire:model="pomodoro.every" suffix="focos" />
                </div>
                <label class="flex cursor-pointer items-center gap-3">
                    <span class="min-w-0 flex-1">
                        <span class="block font-semibold">Tocar alarme</span>
                        <span class="block text-sm text-ink-soft">Som + vibração ao fim do foco e da pausa (com o app aberto).</span>
                    </span>
                    <input type="checkbox" wire:model="timer_sound" class="peer sr-only">
                    <span class="relative h-7 w-12 shrink-0 rounded-full bg-rule transition peer-checked:bg-pen after:absolute after:left-1 after:top-1 after:size-5 after:rounded-full after:bg-white after:transition peer-checked:after:translate-x-5"></span>
                </label>
                <p class="text-sm text-ink-soft">No modo Pomodoro o cronômetro pausa sozinho ao fim de cada foco — mesmo com o app fechado — e o tempo de pausa não conta como estudo. Com as <a href="{{ route('notifications') }}" wire:navigate class="font-semibold text-pen">notificações</a> ativadas, o celular toca o som de notificação mesmo bloqueado.</p>
            </x-ui.card>
        </x-ui.section>

        <x-ui.button class="w-full" icon="check">Salvar</x-ui.button>
    </form>
</div>
