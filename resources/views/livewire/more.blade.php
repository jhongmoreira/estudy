<div class="space-y-6"
     x-data x-on:theme-changed.window="window.applyTheme($event.detail.theme ?? $event.detail[0]?.theme)">
    <x-ui.page-header title="Mais" />

    <a href="{{ route('profile') }}" wire:navigate class="tap flex items-center gap-4 rounded-2xl border border-rule bg-sheet p-4 hover:bg-sheet-2">
        <x-ui.avatar :user="$user" class="size-14 text-lg" />
        <span class="min-w-0 flex-1">
            <span class="block truncate font-display text-lg font-bold">{{ $user->name }}</span>
            <span class="block truncate text-sm text-ink-soft">{{ $user->email }}</span>
        </span>
        <x-icon name="chevron-right" class="text-ink-soft" />
    </a>

    <x-ui.section title="Aparência">
        <x-ui.segmented action="setTheme" :value="$user->theme()" :options="['system' => ['Sistema', 'monitor'], 'light' => ['Claro', 'sun'], 'dark' => ['Escuro', 'moon']]" />
    </x-ui.section>

    <x-ui.section title="Estudos">
        <x-ui.card class="divide-y divide-rule">
            <x-ui.list-item icon="clock" label="Sessões de estudo" description="Histórico e registros manuais" :href="route('sessions')" />
            <x-ui.list-item icon="cycle" label="Ciclo de estudos" description="Rodízio de disciplinas com tempo-alvo" :href="route('cycle')" />
            <x-ui.list-item icon="sliders" label="Configurações de estudo" description="Metas, dias de estudo, Pomodoro" :href="route('settings')" />
            <x-ui.list-item icon="calendar" label="Agenda e notas" description="Provas, trabalhos, entregas e médias" :href="route('agenda')" />
            <x-ui.list-item icon="chart" tone="soft" label="Estatísticas" description="Em breve" disabled class="cursor-default opacity-55 hover:bg-transparent active:scale-100" />
            <x-ui.list-item icon="award" tone="soft" label="Histórico de formação" description="Em breve" disabled class="cursor-default opacity-55 hover:bg-transparent active:scale-100" />
        </x-ui.card>
    </x-ui.section>

    <x-ui.section title="App" x-data x-show="!$store.install.standalone" x-cloak>
        <x-ui.card>
            <x-ui.list-item icon="phone" tone="marker" label="Instalar o Estudy" description="Abra direto da tela inicial, como um app" @click="$store.install.prompt()" :chevron="true" />
        </x-ui.card>
    </x-ui.section>

    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <x-ui.card>
            <button type="submit" class="tap flex w-full items-center gap-3 px-4 py-3.5 text-left font-semibold text-late hover:bg-sheet-2">
                <span class="grid size-10 place-items-center rounded-xl bg-late/10"><x-icon name="log-out" /></span> Sair
            </button>
        </x-ui.card>
    </form>
</div>
