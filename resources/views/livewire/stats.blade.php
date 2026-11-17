@php $fmt = fn (int $s) => \App\Support\Duration::human($s); @endphp
<div>
    <x-ui.page-header title="Estatísticas" :back="route('more')" :subtitle="$summary['from']->format('d/m/Y').' a '.$summary['to']->format('d/m/Y')">
        <a href="{{ route('reports.study', array_filter(['de' => $summary['from']->toDateString(), 'ate' => $summary['to']->toDateString(), 'curso' => $courseId])) }}"
           class="tap flex items-center gap-1.5 rounded-xl px-3 py-2 text-sm font-semibold text-pen hover:bg-pen-soft"><x-icon name="download" class="size-4" /> PDF</a>
    </x-ui.page-header>

    <div class="mb-6 grid grid-cols-2 gap-2 sm:max-w-md">
        <select wire:model.live="period" aria-label="Período" class="rounded-xl border-2 border-rule bg-sheet px-3 py-2.5 text-sm font-semibold outline-none focus:border-pen">
            @foreach (\App\Services\StatsService::PERIODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
        </select>
        <select wire:model.live="courseId" aria-label="Curso" class="rounded-xl border-2 border-rule bg-sheet px-3 py-2.5 text-sm font-semibold outline-none focus:border-pen">
            <option value="">Todos os cursos</option>
            @foreach ($courses as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach
        </select>
    </div>

    <div class="space-y-8" wire:loading.class="opacity-60">
        {{-- Resumo --}}
        <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach ([
                ['Tempo estudado', $fmt($summary['seconds']), $summary['sessions'].' sessões'],
                ['Média por dia estudado', $fmt($summary['avg_per_day']), $goalPct.'% da meta diária'],
                ['Dias com estudo', $summary['days'], 'no período'],
                ['Sequência', $streak['current'].' '.($streak['current'] === 1 ? 'dia' : 'dias'), 'recorde: '.$streak['best']],
            ] as [$label, $value, $sub])
                <div class="rounded-2xl border border-rule bg-sheet p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-ink-soft">{{ $label }}</p>
                    <p class="mt-1 font-display text-2xl font-bold">{{ $value }}</p>
                    <p class="text-xs text-ink-soft">{{ $sub }}</p>
                </div>
            @endforeach
        </div>

        @if ($summary['sessions'] === 0)
            <x-ui.empty icon="chart" title="Sem estudos no período" text="Use o cronômetro ou registre estudos para ver seus números aqui." />
        @else
            <x-ui.section :title="'Tempo por '.$timeline['bucket']">
                <x-ui.card class="p-4"><x-chart.bars :bars="$timeline['bars']" /></x-ui.card>
            </x-ui.section>

            <div class="grid gap-8 lg:grid-cols-2">
                <x-ui.section title="Por curso">
                    <x-ui.card class="p-4"><x-chart.donut :items="$byCourse" /></x-ui.card>
                </x-ui.section>
                <x-ui.section title="Por tipo de estudo">
                    <x-ui.card class="p-4"><x-chart.hbars :items="$byKind" /></x-ui.card>
                </x-ui.section>
            </div>

            <x-ui.section title="Disciplinas que mais estudou">
                <x-ui.card class="p-4"><x-chart.hbars :items="$bySubject" /></x-ui.card>
            </x-ui.section>

            <div class="grid gap-8 lg:grid-cols-2">
                <x-ui.section title="Quando você rende">
                    <x-ui.card class="space-y-5 p-4">
                        @php $bestPart = collect($dayParts)->sortDesc()->keys()->first(); @endphp
                        @if (array_sum($dayParts) > 0)
                            <p class="text-sm">Você estuda mais de <strong class="highlight">{{ mb_strtolower($bestPart) }}</strong>.</p>
                            <x-chart.hbars :items="collect($dayParts)->map(fn ($v, $k) => ['name' => $k, 'value' => $v, 'color' => $k === $bestPart ? 'var(--marker)' : 'var(--pen)'])->values()->all()" />
                        @else
                            <p class="text-sm text-ink-soft">Use o cronômetro (ou informe o horário) para descobrir seu melhor período.</p>
                        @endif
                        <div>
                            <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-soft">Dias da semana</p>
                            <x-chart.bars :height="90" :bars="collect($weekdays)->map(fn ($v, $k) => ['label' => $k, 'title' => $k, 'total' => $v, 'segments' => [['value' => $v, 'color' => 'var(--pen)', 'name' => $k]]])->values()->all()" />
                        </div>
                    </x-ui.card>
                </x-ui.section>

                <x-ui.section title="Exercícios e leitura">
                    <x-ui.card class="space-y-5 p-4">
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div><p class="font-display text-2xl font-bold">{{ $questions['total'] }}</p><p class="text-xs text-ink-soft">questões</p></div>
                            <div><p class="font-display text-2xl font-bold">{{ $questions['total'] > 0 ? round($questions['correct'] / $questions['total'] * 100) : 0 }}%</p><p class="text-xs text-ink-soft">de acerto</p></div>
                            <div><p class="font-display text-2xl font-bold">{{ $reading['pages'] }}</p><p class="text-xs text-ink-soft">páginas{{ $reading['per_hour'] ? ' · '.str_replace('.', ',', $reading['per_hour']).'/h' : '' }}</p></div>
                        </div>
                        @if ($questions['by_subject'] !== [])
                            <ul class="space-y-2.5">
                                @foreach ($questions['by_subject'] as $q)
                                    @php $pct = $q['total'] > 0 ? round($q['correct'] / $q['total'] * 100) : 0; @endphp
                                    <li>
                                        <div class="mb-1 flex justify-between text-sm"><span class="truncate font-semibold">{{ $q['name'] }}</span><span class="shrink-0 text-ink-soft">{{ $q['correct'] }}/{{ $q['total'] }} · <strong class="text-ink">{{ $pct }}%</strong></span></div>
                                        <div class="h-2 overflow-hidden rounded-full bg-late/20"><div class="h-full rounded-full bg-done" style="width: {{ $pct }}%"></div></div>
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <p class="text-sm text-ink-soft">Informe questões e acertos ao encerrar as sessões para acompanhar seu aproveitamento.</p>
                        @endif
                    </x-ui.card>
                </x-ui.section>
            </div>
        @endif

        <div class="grid gap-8 lg:grid-cols-2">
            <x-ui.section title="Revisões">
                <x-ui.card class="space-y-4 p-4">
                    <div class="grid grid-cols-3 gap-2 text-center">
                        <div><p class="font-display text-2xl font-bold">{{ $reviews['done'] }}</p><p class="text-xs text-ink-soft">feitas</p></div>
                        <div><p class="font-display text-2xl font-bold">{{ $reviews['done'] > 0 ? round($reviews['on_time'] / $reviews['done'] * 100) : 0 }}%</p><p class="text-xs text-ink-soft">em dia</p></div>
                        <div><p class="font-display text-2xl font-bold {{ $reviews['overdue'] ? 'text-late' : '' }}">{{ $reviews['overdue'] }}</p><p class="text-xs text-ink-soft">atrasadas</p></div>
                    </div>
                    @if ($reviews['done'] > 0)
                        <div class="flex h-3 overflow-hidden rounded-full">
                            @foreach (['good' => 'var(--done)', 'hard' => 'var(--marker)', 'forgot' => 'var(--late)'] as $k => $color)
                                <div style="width: {{ $reviews['ratings'][$k] / $reviews['done'] * 100 }}%; background: {{ $color }}" title="{{ \App\Models\Review::RATINGS[$k][0] }}: {{ $reviews['ratings'][$k] }}"></div>
                            @endforeach
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs text-ink-soft">
                            @foreach (\App\Models\Review::RATINGS as $k => [$label, $emoji])<span>{{ $emoji }} {{ $label }}: <strong class="text-ink">{{ $reviews['ratings'][$k] }}</strong></span>@endforeach
                        </div>
                    @endif
                </x-ui.card>
            </x-ui.section>

            <x-ui.section title="Mapa de dificuldade">
                <x-ui.card class="p-4">
                    @if ($difficulty === [])
                        <p class="text-sm text-ink-soft">Marque a dificuldade dos tópicos (● ●● ●●●) para ver onde estão seus pontos fracos.</p>
                    @else
                        <ul class="space-y-3">
                            @foreach (array_slice($difficulty, 0, 8) as $d)
                                <li>
                                    <div class="mb-1 flex justify-between gap-2 text-sm">
                                        <span class="truncate font-semibold">{{ $d['name'] }}</span>
                                        <span class="shrink-0 text-xs text-ink-soft">{{ $d['done'] }}/{{ $d['total'] }} concluídos @if ($d['forgot']) · {{ $d['forgot'] }}× esqueci @endif</span>
                                    </div>
                                    <div class="flex h-2.5 overflow-hidden rounded-full bg-sheet-2" title="Fácil {{ $d['easy'] }} · Médio {{ $d['medium'] }} · Difícil {{ $d['hard'] }}">
                                        <div class="bg-done" style="width: {{ $d['easy'] / $d['total'] * 100 }}%"></div>
                                        <div class="bg-marker" style="width: {{ $d['medium'] / $d['total'] * 100 }}%"></div>
                                        <div class="bg-late" style="width: {{ $d['hard'] / $d['total'] * 100 }}%"></div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                        <div class="mt-3 flex gap-3 text-xs text-ink-soft"><span>● fácil</span><span>●● médio</span><span>●●● difícil</span></div>
                    @endif
                </x-ui.card>
            </x-ui.section>
        </div>

        <x-ui.section title="Constância no último ano">
            <x-ui.card class="overflow-x-auto p-4">
                <x-ui.heatmap :totals="$yearTotals" :weeks="53" :goal="$user->dailyGoalMinutes() * 60" :min="(int) $user->setting('streak_min') * 60" :week-start="$user->weekStartsOn()" :scale="1" class="!max-w-none" />
            </x-ui.card>
        </x-ui.section>
    </div>
</div>
