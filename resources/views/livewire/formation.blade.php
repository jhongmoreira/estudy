<div x-data>
    <x-ui.page-header title="Formação" :back="route('more')" subtitle="Seu histórico de cursos, horas e certificados">
        <a href="{{ route('formation.csv') }}" class="tap rounded-full p-2.5 text-ink-soft hover:bg-sheet-2 print:hidden" aria-label="Exportar CSV" title="Exportar CSV"><x-icon name="download" /></a>
        <button type="button" onclick="window.print()" class="tap rounded-full p-2.5 text-ink-soft hover:bg-sheet-2 print:hidden" aria-label="Imprimir" title="Imprimir"><x-icon name="clipboard" /></button>
    </x-ui.page-header>

    {{-- Totais --}}
    <div class="mb-6 rounded-2xl border border-rule bg-sheet p-5">
        <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">Total em cursos concluídos</p>
        <p class="font-display text-3xl font-bold">{{ $totalHours }} h</p>
        @if ($byType->isNotEmpty())
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($byType as $type => $t)
                    <span class="rounded-full bg-sheet-2 px-3 py-1 text-sm"><strong>{{ \App\Models\Course::TYPES[$type][0] ?? $type }}</strong> · {{ $t['count'] }} · {{ $t['hours'] }} h</span>
                @endforeach
            </div>
        @endif
        @if ($byYear->isNotEmpty())
            <p class="mt-3 text-sm text-ink-soft">
                @foreach ($byYear as $year => $h){{ $year }}: <strong class="text-ink">{{ $h }} h</strong>@if (! $loop->last) · @endif @endforeach
            </p>
        @endif
        <p class="mt-2 text-xs text-ink-soft">Usa a carga horária do certificado; sem ela, as horas registradas no Estudy. Útil para horas complementares e Lattes.</p>
    </div>

    <div class="mb-4 flex gap-2 print:hidden">
        @foreach (['concluidos' => 'Concluídos', 'andamento' => 'Em andamento'] as $k => $l)
            <button type="button" wire:click="$set('tab', '{{ $k }}')" @class(['tap rounded-full border-2 px-4 py-1.5 text-sm font-semibold', 'border-ink bg-ink text-paper' => $tab === $k, 'border-rule bg-sheet text-ink-soft' => $tab !== $k])>{{ $l }}</button>
        @endforeach
    </div>

    @if ($courses->isEmpty())
        <x-ui.empty icon="award" :title="$tab === 'concluidos' ? 'Nenhum curso concluído ainda' : 'Nenhum curso em andamento'"
                    :text="$tab === 'concluidos' ? 'Quando terminar um curso, mude a situação para Concluído — ele aparece aqui com a carga horária e o certificado.' : null" />
    @else
        <x-ui.card class="divide-y divide-rule">
            @foreach ($courses as $c)
                @php $logged = intdiv((int) ($hours[$c->id] ?? 0), 3600); @endphp
                <div class="flex flex-wrap items-center gap-3 px-4 py-4" wire:key="f-{{ $c->id }}">
                    <span class="grid size-11 shrink-0 place-items-center rounded-xl text-xl" style="background: color-mix(in srgb, {{ $c->color }} 15%, transparent); color: {{ $c->color }}">
                        @if ($c->emoji) {{ $c->emoji }} @else <x-icon :name="$c->typeIcon()" class="size-6" /> @endif
                    </span>
                    <a href="{{ route('courses.show', $c) }}" wire:navigate class="min-w-0 flex-1 basis-48">
                        <p class="truncate font-semibold">{{ $c->name }}</p>
                        <p class="truncate text-xs text-ink-soft">
                            {{ $c->typeLabel() }}@if ($c->institution) · {{ $c->institution }}@endif
                            @if ($c->started_on || $c->completed_on) · {{ $c->started_on?->format('m/Y') ?? '?' }} – {{ $c->completed_on?->format('m/Y') ?? 'atual' }}@endif
                        </p>
                        <p class="text-xs">
                            @if ($c->workload_hours)<strong>{{ $c->workload_hours }} h</strong> de carga horária · @endif
                            <span class="text-ink-soft">{{ $logged }} h registradas</span>
                        </p>
                    </a>
                    <div class="ml-14 flex shrink-0 items-center gap-1 sm:ml-0 print:hidden">
                        @if ($c->certificate)
                            <a href="{{ route('formation.certificate', $c) }}" class="tap flex items-center gap-1 rounded-xl bg-done/15 px-3 py-2 text-xs font-semibold text-done" title="{{ $c->certificate->original_name }}">
                                <x-icon name="download" class="size-4" /> Certificado
                            </a>
                            <button type="button" wire:click="removeCertificate({{ $c->id }})" wire:confirm="Remover o certificado?" class="tap rounded-lg p-2 text-ink-soft hover:bg-sheet-2" aria-label="Remover certificado"><x-icon name="x" class="size-4" /></button>
                        @else
                            <button type="button" wire:click="startUpload({{ $c->id }})" class="tap flex items-center gap-1 rounded-xl border border-dashed border-rule px-3 py-2 text-xs font-semibold text-pen">
                                <x-icon name="plus" class="size-4" /> Certificado
                            </button>
                        @endif
                    </div>
                </div>
            @endforeach
        </x-ui.card>
    @endif

    <x-ui.sheet name="certificate" title="Enviar certificado">
        <form wire:submit="saveCertificate" class="space-y-4">
            <label class="flex cursor-pointer flex-col items-center gap-2 rounded-2xl border-2 border-dashed border-rule px-4 py-8 text-center hover:border-pen">
                <x-icon name="download" class="size-8 rotate-180 text-pen" />
                <span class="font-semibold">{{ $file ? $file->getClientOriginalName() : 'Escolher arquivo' }}</span>
                <span class="text-xs text-ink-soft">PDF ou imagem, até 5 MB. Fica guardado só para você.</span>
                <input type="file" wire:model="file" accept=".pdf,image/png,image/jpeg,image/webp" class="sr-only">
            </label>
            <div wire:loading wire:target="file" class="text-sm text-ink-soft">Enviando…</div>
            @error('file') <p class="text-sm text-late">{{ $message }}</p> @enderror
            <x-ui.button class="w-full" icon="check" wire:loading.attr="disabled" wire:target="file">Salvar</x-ui.button>
        </form>
    </x-ui.sheet>
</div>
