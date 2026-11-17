<div>
    <x-ui.page-header title="Buscar" :back="route('courses')" />

    <div class="relative">
        <x-icon name="search" class="pointer-events-none absolute left-4 top-1/2 size-5 -translate-y-1/2 text-ink-soft" />
        <input type="search" wire:model.live.debounce.250ms="q" autofocus placeholder="Cursos, disciplinas, módulos ou tópicos"
               class="block w-full rounded-2xl border-2 border-rule bg-sheet py-3.5 pl-12 pr-4 outline-none focus:border-pen" aria-label="Buscar">
    </div>

    <div class="mt-6 space-y-6">
        @if (! $searched)
            <p class="text-center text-sm text-ink-soft">Digite pelo menos 2 letras.</p>
        @elseif ($courses->isEmpty() && $subjects->isEmpty() && $topics->isEmpty())
            <x-ui.empty icon="search" title="Nada encontrado" text="Tente outra palavra." />
        @else
            @if ($courses->isNotEmpty())
                <x-ui.section title="Cursos">
                    <x-ui.card class="divide-y divide-rule">
                        @foreach ($courses as $item)
                            <x-ui.list-item :icon="$item->typeIcon()" :label="$item->name" :description="$item->typeLabel()" :href="route('courses.show', $item)" />
                        @endforeach
                    </x-ui.card>
                </x-ui.section>
            @endif
            @if ($subjects->isNotEmpty())
                <x-ui.section title="Disciplinas e módulos">
                    <x-ui.card class="divide-y divide-rule">
                        @foreach ($subjects as $item)
                            <x-ui.list-item icon="list" :label="$item->name" :description="$item->course->name" :href="route('subjects.show', $item)" />
                        @endforeach
                    </x-ui.card>
                </x-ui.section>
            @endif
            @if ($topics->isNotEmpty())
                <x-ui.section title="Tópicos">
                    <x-ui.card class="divide-y divide-rule">
                        @foreach ($topics as $item)
                            <x-ui.list-item :icon="$item->isDone() ? 'check' : 'book'" :tone="$item->isDone() ? 'soft' : 'pen'" :label="$item->title"
                                            :description="$item->subject->course->name.' · '.$item->subject->name" :href="route('subjects.show', $item->subject)" />
                        @endforeach
                    </x-ui.card>
                </x-ui.section>
            @endif
        @endif
    </div>
</div>
