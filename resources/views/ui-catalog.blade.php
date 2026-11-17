{{-- /_ui — catálogo de componentes (só em desenvolvimento) para conferir os dois temas --}}
<x-layouts.app title="Componentes">
    <x-ui.page-header title="Componentes" subtitle="Catálogo visual do Estudy" />
    <div class="space-y-8" x-data>
        <x-ui.section title="Cores">
            <div class="grid grid-cols-4 gap-2 sm:grid-cols-6">
                @foreach (['paper', 'sheet', 'sheet-2', 'rule', 'ink', 'ink-soft', 'pen', 'pen-soft', 'marker', 'late', 'done'] as $c)
                    <div class="rounded-xl border border-rule p-2 text-xs"><div class="mb-1 h-10 rounded-lg" style="background: var(--{{ $c }})"></div>{{ $c }}</div>
                @endforeach
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (\App\Support\Palette::COLORS as $hex => $name)
                    <span class="rounded-full px-3 py-1 text-xs font-semibold" style="background: {{ $hex }}; color: {{ \App\Support\Palette::onColor($hex) }}">{{ $name }}</span>
                @endforeach
            </div>
        </x-ui.section>
        <x-ui.section title="Botões">
            <div class="flex flex-wrap gap-2">
                <x-ui.button type="button" icon="play">Primário</x-ui.button>
                <x-ui.button type="button" variant="secondary">Secundário</x-ui.button>
                <x-ui.button type="button" variant="marker" icon="highlighter">Marca-texto</x-ui.button>
                <x-ui.button type="button" variant="danger">Perigo</x-ui.button>
                <x-ui.button type="button" variant="ghost">Fantasma</x-ui.button>
            </div>
        </x-ui.section>
        <x-ui.section title="Cards e progresso">
            <div class="grid gap-3 sm:grid-cols-2">
                <x-ui.card stripe="#7c3aed" class="space-y-2 p-4">
                    <p class="font-semibold">Cálculo II</p><p class="text-sm text-ink-soft">3h10 de 5h esta semana</p>
                    <x-ui.progress :value="190" :max="300" color="#7c3aed" />
                </x-ui.card>
                <x-ui.card stripe="#059669" class="space-y-2 p-4">
                    <p class="font-semibold">Inglês — B2</p><p class="text-sm text-ink-soft">Meta batida!</p>
                    <x-ui.progress :value="1" :max="1" />
                </x-ui.card>
                <x-ui.card class="flex items-center gap-4 p-4"><x-ui.ring :value="25" :max="60" :size="80" :stroke="8"><b>25</b></x-ui.ring> Anel em andamento</x-ui.card>
                <x-ui.card class="flex items-center gap-4 p-4"><x-ui.ring :value="60" :max="60" :size="80" :stroke="8"><b>60</b></x-ui.ring> Anel completo</x-ui.card>
            </div>
        </x-ui.section>
        <x-ui.section title="Lista, toast e folha">
            <x-ui.card class="divide-y divide-rule">
                <x-ui.list-item icon="bell" label="Mostrar toast" description="Sucesso" @click="$store.toast.show('Sessão salva!')" />
                <x-ui.list-item icon="alert" tone="late" label="Toast de erro" @click="$store.toast.show('Algo deu errado.', 'error')" />
                <x-ui.list-item icon="sparkles" tone="marker" label="Abrir folha" @click="$dispatch('open-sheet', 'demo')" :chevron="true" />
            </x-ui.card>
            <x-ui.sheet name="demo" title="Como foi?"><p class="text-ink-soft">Conteúdo da folha.</p></x-ui.sheet>
        </x-ui.section>
        <x-ui.empty icon="cap" title="Estado vazio" text="Texto de apoio do estado vazio." />
    </div>
</x-layouts.app>
