<div x-data x-init="@if ($new) $nextTick(() => $dispatch('edit-course', { id: null })) @endif">
    <x-ui.page-header title="Cursos" subtitle="Tudo o que você está estudando">
        <a href="{{ route('search') }}" wire:navigate class="tap rounded-full p-2.5 text-ink-soft hover:bg-sheet-2" aria-label="Buscar"><x-icon name="search" class="size-6" /></a>
        <x-ui.button type="button" icon="plus" class="!px-4 !py-2.5 max-sm:!hidden" @click="$dispatch('edit-course', { id: null })">Novo curso</x-ui.button>
    </x-ui.page-header>

    {{-- Abas --}}
    <div class="-mx-4 mb-5 flex gap-2 overflow-x-auto px-4 pb-1 lg:mx-0 lg:px-0">
        @foreach (\App\Livewire\Courses::TABS as $key => [$label])
            <button type="button" wire:click="setTab('{{ $key }}')" aria-pressed="{{ $tab === $key ? 'true' : 'false' }}"
                    @class(['tap shrink-0 rounded-full border-2 px-4 py-1.5 text-sm font-semibold',
                            'border-ink bg-ink text-paper' => $tab === $key,
                            'border-rule bg-sheet text-ink-soft' => $tab !== $key])>
                {{ $label }} @if ($tabCounts[$key] > 0)<span class="ml-1 opacity-70">{{ $tabCounts[$key] }}</span>@endif
            </button>
        @endforeach
    </div>

    @if ($courses->isEmpty())
        @if ($tab === 'active')
            <x-ui.empty icon="cap" title="Comece cadastrando um curso"
                        text="Graduação, pós, curso online, idioma ou aquele livro — você define as disciplinas ou módulos e os tópicos.">
                <x-ui.button type="button" icon="plus" @click="$dispatch('edit-course', { id: null })">Novo curso</x-ui.button>
            </x-ui.empty>
        @else
            <x-ui.empty icon="book" title="Nada por aqui" text="Nenhum curso com essa situação." />
        @endif
    @else
        @if ($courses->count() > 1)
            <div class="mb-3 flex justify-end">
                <button type="button" wire:click="$toggle('organizing')" class="tap flex items-center gap-1.5 text-sm font-semibold text-pen">
                    <x-icon :name="$organizing ? 'check' : 'arrows'" class="size-4" /> {{ $organizing ? 'Concluir' : 'Organizar' }}
                </button>
            </div>
        @endif

        <div class="grid gap-3 sm:grid-cols-2">
            @foreach ($courses as $item)
                <div class="flex min-w-0 items-stretch gap-2" wire:key="course-{{ $item->id }}">
                    <x-ui.course-card :course="$item" :stats="$stats[$item->id]" :week="$courseWeek[$item->id] ?? 0" class="flex-1" />
                    @if ($organizing)
                        <div class="flex flex-col justify-center gap-1">
                            <button type="button" wire:click="move({{ $item->id }}, -1)" @disabled($loop->first) class="tap rounded-xl border border-rule bg-sheet p-2 disabled:opacity-30" aria-label="Subir"><x-icon name="arrow-up" class="size-4" /></button>
                            <button type="button" wire:click="move({{ $item->id }}, 1)" @disabled($loop->last) class="tap rounded-xl border border-rule bg-sheet p-2 disabled:opacity-30" aria-label="Descer"><x-icon name="arrow-down" class="size-4" /></button>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif

    {{-- Botão flutuante no celular --}}
    <button type="button" @click="$dispatch('edit-course', { id: null })" aria-label="Novo curso"
            class="tap fixed bottom-24 right-4 z-20 grid size-14 place-items-center rounded-2xl bg-marker text-[#1f2a44] shadow-lg sm:hidden">
        <x-icon name="plus" class="size-7" />
    </button>
</div>
