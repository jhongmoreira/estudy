<div>
    <x-ui.sheet name="paste-list" title="Colar lista">
        <form wire:submit="save" class="space-y-4">
            <p class="text-sm text-ink-soft">
                Cole uma ementa, o sumário de um livro ou a grade de um curso online em <strong class="text-ink">{{ $target }}</strong>.
                Cada linha vira um tópico.
                @if (! $subjectId)
                    Linhas em <strong class="text-ink">CAIXA ALTA</strong>, terminadas em <strong class="text-ink">“:”</strong> ou seguidas de itens recuados viram {{ $unitPlural }}.
                @endif
                Durações como <code class="rounded bg-sheet-2 px-1">(45min)</code> viram estimativa.
            </p>

            <x-ui.textarea name="text" wire:model.live.debounce.500ms="text" rows="8" class="font-mono text-sm"
                           placeholder="UNIDADE 1 — FUNDAMENTOS&#10;- Introdução (30min)&#10;- Variáveis e tipos&#10;UNIDADE 2 — ESTRUTURAS&#10;- Listas e tuplas (1h)" />

            @if ($preview !== [])
                <div class="rounded-2xl border border-rule bg-paper p-3">
                    <p class="mb-2 text-sm font-semibold">
                        Prévia: <span class="highlight">{{ $topicCount }} {{ $topicCount === 1 ? 'tópico' : 'tópicos' }}</span>
                        @if ($subjectCount > 0) em {{ $subjectCount }} {{ $subjectCount === 1 ? rtrim($unitPlural, 's') : $unitPlural }} @endif
                    </p>
                    <div class="max-h-56 space-y-2 overflow-y-auto text-sm">
                        @foreach ($preview as $group)
                            <div>
                                @if ($group['subject'])
                                    <p class="font-semibold text-pen">{{ $group['subject'] }}</p>
                                @elseif (! $subjectId)
                                    <p class="font-semibold text-ink-soft">Conteúdo</p>
                                @endif
                                <ul class="space-y-0.5 pl-3">
                                    @foreach ($group['topics'] as $t)
                                        <li class="flex justify-between gap-2"><span class="truncate">• {{ $t['title'] }}</span>@if ($t['minutes'])<span class="shrink-0 text-ink-soft">{{ \App\Support\Duration::human($t['minutes'] * 60) }}</span>@endif</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <x-ui.button class="w-full" icon="plus" :disabled="$preview === []">Adicionar</x-ui.button>
        </form>
    </x-ui.sheet>
</div>
