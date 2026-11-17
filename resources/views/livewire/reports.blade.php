<div>
    <x-ui.page-header title="Relatórios" :back="route('more')" subtitle="Baixe em PDF para guardar, imprimir ou enviar" />

    <div class="grid max-w-3xl gap-4 lg:grid-cols-2">
        {{-- Estudos do período --}}
        <x-ui.card class="space-y-4 p-5 lg:col-span-2">
            <div class="flex items-start gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-pen-soft text-pen"><x-icon name="chart" class="size-6" /></span>
                <div>
                    <h2 class="font-bold">Relatório de estudos</h2>
                    <p class="text-sm text-ink-soft">Tempo por curso, disciplina, tipo e semana; dias estudados, acertos, leitura e revisões.</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                @foreach (['week' => 'Esta semana', 'month' => 'Este mês', 'last_month' => 'Mês passado', '30' => '30 dias', 'semester' => 'Semestre', 'year' => 'Este ano'] as $k => $l)
                    <button type="button" wire:click="preset('{{ $k }}')" class="tap rounded-full bg-sheet-2 px-3 py-1 text-sm font-semibold text-ink-soft hover:text-ink">{{ $l }}</button>
                @endforeach
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
                <x-ui.input label="De" name="from" type="date" wire:model.live="from" />
                <x-ui.input label="Até" name="to" type="date" wire:model.live="to" />
                <div class="col-span-2 sm:col-span-1">
                    <x-ui.select label="Curso" name="studyCourse" wire:model.live="studyCourse" :options="$courses->pluck('name', 'id')->all()" placeholder="Todos" />
                </div>
            </div>
            <label class="flex cursor-pointer items-center gap-3 text-sm">
                <input type="checkbox" wire:model.live="withSessions" class="size-5 accent-[#3047c9]">
                Incluir a lista de todas as sessões
            </label>
            <x-ui.button :href="$studyUrl" :navigate="false" icon="download" class="w-full sm:w-auto">Baixar PDF</x-ui.button>
        </x-ui.card>

        {{-- Boletim --}}
        <x-ui.card class="space-y-4 p-5">
            <div class="flex items-start gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-marker/40 text-marker-ink"><x-icon name="clipboard" class="size-6" /></span>
                <div>
                    <h2 class="font-bold">Boletim de notas</h2>
                    <p class="text-sm text-ink-soft">Avaliações, médias ponderadas e situação de cada disciplina.</p>
                </div>
            </div>
            <x-ui.select label="Curso" name="gradesCourse" wire:model.live="gradesCourse" :options="$courses->pluck('name', 'id')->all()" placeholder="Escolha…" />
            @if ($gradesCourse)
                <x-ui.button :href="route('reports.grades', $gradesCourse)" :navigate="false" icon="download" variant="secondary" class="w-full">Baixar PDF</x-ui.button>
            @endif
        </x-ui.card>

        {{-- Progresso do curso --}}
        <x-ui.card class="space-y-4 p-5">
            <div class="flex items-start gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-done/15 text-done"><x-icon name="list" class="size-6" /></span>
                <div>
                    <h2 class="font-bold">Progresso do curso</h2>
                    <p class="text-sm text-ink-soft">Disciplinas, tópicos concluídos, horas, revisões e prazos — um retrato do curso.</p>
                </div>
            </div>
            <x-ui.select label="Curso" name="progressCourse" wire:model.live="progressCourse" :options="$courses->pluck('name', 'id')->all()" placeholder="Escolha…" />
            @if ($progressCourse)
                <x-ui.button :href="route('reports.course', $progressCourse)" :navigate="false" icon="download" variant="secondary" class="w-full">Baixar PDF</x-ui.button>
            @endif
        </x-ui.card>

        {{-- Formação --}}
        <x-ui.card class="space-y-4 p-5 lg:col-span-2">
            <div class="flex items-start gap-3">
                <span class="grid size-11 shrink-0 place-items-center rounded-xl bg-pen-soft text-pen"><x-icon name="award" class="size-6" /></span>
                <div>
                    <h2 class="font-bold">Histórico de formação</h2>
                    <p class="text-sm text-ink-soft">Cursos concluídos com carga horária, horas por tipo e por ano — útil para horas complementares e Lattes.</p>
                </div>
            </div>
            <label class="flex cursor-pointer items-center gap-3 text-sm">
                <input type="checkbox" wire:model.live="withInProgress" class="size-5 accent-[#3047c9]">
                Incluir cursos em andamento
            </label>
            <x-ui.button :href="route('reports.formation', $withInProgress ? ['andamento' => 1] : [])" :navigate="false" icon="download" variant="secondary" class="w-full sm:w-auto">Baixar PDF</x-ui.button>
        </x-ui.card>
    </div>
</div>
