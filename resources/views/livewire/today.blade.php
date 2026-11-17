@php
    $goalSeconds = $goalMinutes * 60;
    $weekdays = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
    $streakMin = (int) $user->setting('streak_min');
@endphp
<div class="space-y-8" x-data>
    <header class="pt-6 lg:pt-10">
        <div class="flex items-center justify-between gap-3">
            <p class="text-sm font-medium text-ink-soft">{{ \Illuminate\Support\Str::ucfirst($today->translatedFormat('l, j \d\e F')) }}</p>
            <a href="{{ route('notifications') }}" wire:navigate class="tap relative -mr-2 rounded-full p-2 text-ink-soft hover:bg-sheet-2 lg:hidden" aria-label="Notificações">
                <x-icon name="bell" class="size-6" />
                @if ($unread > 0)<span class="absolute right-1 top-1 grid h-4 min-w-4 place-items-center rounded-full bg-pen px-1 text-[10px] font-bold text-white dark:text-[#14161c]">{{ $unread }}</span>@endif
            </a>
        </div>
        <h1 class="mt-1 text-3xl font-bold lg:text-4xl">{{ $greeting }}, <span class="highlight">{{ $user->firstName() }}</span></h1>
    </header>

    {{-- Meta do dia + sequência + botão de estudar --}}
    <div class="grid gap-4 lg:grid-cols-[1fr_1.2fr]">
        <x-ui.card class="flex items-center gap-5 p-5">
            <x-ui.ring :value="$todaySeconds" :max="$goalSeconds" :size="112" :stroke="11">
                <span class="block font-display text-2xl font-bold leading-none">{{ intdiv($todaySeconds, 60) }}</span>
                <span class="text-xs text-ink-soft">de {{ $goalMinutes }} min</span>
            </x-ui.ring>
            <div class="min-w-0">
                <p class="text-sm font-semibold uppercase tracking-wide text-ink-soft">Meta de hoje</p>
                <p class="mt-1 font-display text-xl font-bold">
                    @if ($todaySeconds >= $goalSeconds)
                        Meta batida! <span class="highlight">Mandou bem.</span>
                    @elseif ($todaySeconds > 0)
                        Faltam {{ \App\Support\Duration::human($goalSeconds - $todaySeconds) }}
                    @else
                        Ainda não começou
                    @endif
                </p>
                <p class="mt-2 flex items-center gap-1.5 text-sm">
                    <x-icon name="flame" @class(['size-5', 'text-[#ea580c]' => $streak['current'] > 0, 'text-ink-soft' => $streak['current'] === 0]) />
                    @if ($streak['current'] > 0)
                        <strong>{{ $streak['current'] }} {{ $streak['current'] === 1 ? 'dia seguido' : 'dias seguidos' }}</strong>
                        @unless ($streak['today_counts'])<span class="text-ink-soft">· estude {{ $streakMin }} min hoje</span>@endunless
                    @else
                        <span class="text-ink-soft">Comece uma sequência hoje</span>
                    @endif
                </p>
                @if ($streak['best'] > 1)<p class="text-xs text-ink-soft">Recorde: {{ $streak['best'] }} dias</p>@endif
            </div>
        </x-ui.card>

        <a href="{{ $running ? route('focus') : route('study') }}" wire:navigate
           class="tap group relative flex items-center justify-between overflow-hidden rounded-2xl bg-pen p-5 text-white dark:text-[#14161c]">
            <span class="min-w-0">
                @if ($running)
                    <span class="block text-sm font-semibold uppercase tracking-wide opacity-80">Cronômetro ligado</span>
                    <span class="mt-1 block font-display text-2xl font-bold">Continuar estudando</span>
                    <span class="mt-1 block truncate text-sm opacity-80">{{ $running->subject->name }}</span>
                @else
                    <span class="block text-sm font-semibold uppercase tracking-wide opacity-80">Pronto para focar?</span>
                    <span class="mt-1 block font-display text-2xl font-bold">Estudar agora</span>
                    <span class="mt-1 block text-sm opacity-80">Cronômetro com modo foco e Pomodoro</span>
                @endif
            </span>
            <span class="grid size-16 shrink-0 place-items-center rounded-2xl bg-white/15 transition group-hover:scale-105">
                <x-icon name="play" class="size-8 translate-x-0.5" />
            </span>
            <span class="pointer-events-none absolute -bottom-6 -right-6 size-28 rounded-full bg-marker/25"></span>
        </a>
    </div>

    @if (! $hasPush)
        <div x-data="{ hidden: (() => { try { return localStorage.getItem('estudy.pushInvite') === '0' } catch (e) { return false } })() }" x-show="!hidden" x-cloak
             class="flex items-center gap-3 rounded-2xl border border-dashed border-pen/40 bg-pen-soft/40 p-4">
            <x-icon name="bell" class="size-6 shrink-0 text-pen" />
            <p class="min-w-0 flex-1 text-sm"><strong>Ative os lembretes</strong> para não esquecer de estudar, revisar e entregar trabalhos.</p>
            <a href="{{ route('notifications') }}" wire:navigate class="tap shrink-0 rounded-xl bg-pen px-3 py-2 text-sm font-semibold text-white dark:text-[#14161c]">Ativar</a>
            <button type="button" @click="hidden = true; try { localStorage.setItem('estudy.pushInvite', '0') } catch (e) {}" class="tap shrink-0 rounded-lg p-1 text-ink-soft" aria-label="Dispensar"><x-icon name="x" class="size-4" /></button>
        </div>
    @endif

    <div class="-mt-4 flex justify-end gap-4 text-sm font-semibold">
        <a href="{{ route('sessions') }}" wire:navigate class="text-ink-soft hover:text-ink">Histórico</a>
        <button type="button" @click="$dispatch('log-session', {})" class="text-pen">+ Registrar estudo feito fora</button>
    </div>

    {{-- O que estudar agora --}}
    @if ($suggestions !== [] && ! $running)
        <x-ui.section title="O que estudar agora">
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($suggestions as $sug)
                    <a href="{{ route('study', array_filter(['disciplina' => $sug['subject_id'], 'topico' => $sug['topic_id'], 'tipo' => $sug['kind'] !== 'theory' ? $sug['kind'] : null])) }}" wire:navigate
                       class="tap relative flex min-w-0 items-center gap-3 overflow-hidden rounded-2xl border border-rule bg-sheet p-4 pl-5 hover:bg-sheet-2">
                        <span class="absolute inset-y-0 left-0 w-1.5" style="background: {{ $sug['color'] }}"></span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-xs font-semibold text-pen">{{ $sug['reason'] }}</span>
                            <span class="block truncate font-semibold">{{ $sug['title'] }}</span>
                            <span class="block truncate text-xs text-ink-soft">{{ $sug['detail'] }}</span>
                        </span>
                        <span class="grid size-10 shrink-0 place-items-center rounded-xl bg-pen-soft text-pen"><x-icon name="play" class="size-5" /></span>
                    </a>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    @if ($dueCount > 0)
        <x-ui.section title="Revisões de hoje" :action="$dueCount > 5 ? 'Ver todas ('.$dueCount.')' : 'Abrir'" :action-href="route('reviews')">
            <x-ui.card class="divide-y divide-rule">
                @foreach ($dueReviews as $r)
                    <x-ui.review-row :review="$r" :steps="count($user->reviewIntervals())">
                        <a href="{{ route('study', ['topico' => $r->topic_id, 'tipo' => 'review']) }}" wire:navigate class="tap grid size-9 shrink-0 place-items-center rounded-xl bg-pen text-white dark:text-[#14161c]" aria-label="Revisar"><x-icon name="play" class="size-4" /></a>
                    </x-ui.review-row>
                @endforeach
            </x-ui.card>
        </x-ui.section>
    @endif

    @if ($agenda->isNotEmpty())
        <x-ui.section title="Próximos prazos" action="Agenda" :action-href="route('agenda')">
            <x-ui.card class="divide-y divide-rule">
                @foreach ($agenda as $item) <x-ui.agenda-row :item="$item" :toggle="false" /> @endforeach
            </x-ui.card>
        </x-ui.section>
    @endif

    {{-- Semana + constância --}}
    <x-ui.section title="Sua semana">
        <x-ui.card class="p-4">
            <div class="mb-3 flex items-baseline justify-between">
                <span class="font-display text-2xl font-bold">{{ \App\Support\Duration::human($weekSeconds) }}</span>
                <span class="text-sm text-ink-soft">meta: {{ \App\Support\Duration::human($goalSeconds * count($user->studyDays())) }}</span>
            </div>
            <div class="grid grid-cols-7 gap-2 text-center">
                @foreach ($week->days() as $day)
                    @php
                        $isToday = $day->isSameDay($today);
                        $studyDay = $user->studiesOn($day);
                        $secs = $daily[$day->toDateString()] ?? 0;
                        $counted = $secs >= $streakMin * 60;
                    @endphp
                    <div class="flex flex-col items-center gap-1.5">
                        <span @class(['text-xs font-semibold uppercase', 'text-pen' => $isToday, 'text-ink-soft' => ! $isToday])>{{ $weekdays[(int) $day->format('w')] }}</span>
                        <span @class(['grid size-10 place-items-center rounded-xl font-display font-bold',
                                      'bg-marker text-[#1f2a44]' => $counted,
                                      'ring-2 ring-pen ring-offset-2 ring-offset-sheet' => $isToday,
                                      'bg-pen text-white dark:text-[#14161c]' => $isToday && ! $counted,
                                      'border-2 border-dashed border-rule text-ink-soft' => ! $isToday && ! $counted && $studyDay,
                                      'text-ink-soft/50' => ! $isToday && ! $counted && ! $studyDay])>{{ $day->day }}</span>
                        <span class="h-4 text-[11px] font-semibold text-ink-soft">{{ $secs > 0 ? \App\Support\Duration::short($secs) : '' }}</span>
                    </div>
                @endforeach
            </div>
            <div class="mt-4 border-t border-rule pt-4">
                <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-ink-soft">Últimas 8 semanas</p>
                <x-ui.heatmap :totals="$daily" :weeks="8" :goal="$goalSeconds" :min="$streakMin * 60" :week-start="$user->weekStartsOn()" />
                <div class="mt-2 flex items-center gap-3 text-[11px] text-ink-soft">
                    <span class="flex items-center gap-1"><span class="size-2.5 rounded-sm bg-sheet-2"></span>nada</span>
                    <span class="flex items-center gap-1"><span class="size-2.5 rounded-sm" style="background: color-mix(in srgb, var(--pen) 25%, transparent)"></span>pouco</span>
                    <span class="flex items-center gap-1"><span class="size-2.5 rounded-sm" style="background: color-mix(in srgb, var(--pen) 65%, transparent)"></span>sequência</span>
                    <span class="flex items-center gap-1"><span class="size-2.5 rounded-sm bg-marker"></span>meta</span>
                </div>
            </div>
        </x-ui.card>
    </x-ui.section>

    <x-ui.section title="Em andamento" :action="$courses->isNotEmpty() ? 'Ver todos' : null" :action-href="route('courses')">
        @if ($courses->isEmpty())
            <x-ui.empty icon="cap" title="Nenhum curso por aqui ainda"
                        text="Cadastre o que você estuda — graduação, pós, curso livre, idioma ou aquele livro — com disciplinas ou módulos e tópicos.">
                <x-ui.button :href="route('courses', ['novo' => 1])" icon="plus">Criar meu primeiro curso</x-ui.button>
            </x-ui.empty>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($courses as $item)
                    <x-ui.course-card :course="$item" :stats="$courseStats[$item->id]" :week="$courseWeek[$item->id] ?? 0" wire:key="today-course-{{ $item->id }}" />
                @endforeach
            </div>
        @endif
    </x-ui.section>
</div>
