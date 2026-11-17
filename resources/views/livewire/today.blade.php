@php
    $goalSeconds = $goalMinutes * 60;
    $todayMinutes = intdiv($todaySeconds, 60);
    $weekdays = ['dom', 'seg', 'ter', 'qua', 'qui', 'sex', 'sáb'];
@endphp
<div class="space-y-8">
    <header class="pt-6 lg:pt-10">
        <p class="text-sm font-medium text-ink-soft">{{ \Illuminate\Support\Str::ucfirst($today->translatedFormat('l, j \d\e F')) }}</p>
        <h1 class="mt-1 text-3xl font-bold lg:text-4xl">{{ $greeting }}, <span class="highlight">{{ $user->firstName() }}</span></h1>
    </header>

    {{-- Meta do dia + botão de estudar: o coração da tela --}}
    <div class="grid gap-4 lg:grid-cols-[1fr_1.2fr]">
        <x-ui.card class="flex items-center gap-5 p-5">
            <x-ui.ring :value="$todaySeconds" :max="$goalSeconds" :size="112" :stroke="11">
                <span class="block font-display text-2xl font-bold leading-none">{{ $todayMinutes }}</span>
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
                <a href="{{ route('settings') }}" wire:navigate class="mt-1 inline-block text-sm font-medium text-pen">Ajustar meta</a>
            </div>
        </x-ui.card>

        <a href="{{ $running ? route('focus') : route('study') }}" wire:navigate
           class="tap group relative flex items-center justify-between overflow-hidden rounded-2xl bg-pen p-5 text-white dark:text-[#14161c]">
            <span>
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

    <div class="-mt-4 flex justify-end gap-4 text-sm font-semibold" x-data>
        <a href="{{ route('sessions') }}" wire:navigate class="text-ink-soft hover:text-ink">Histórico</a>
        <button type="button" @click="$dispatch('log-session', {})" class="text-pen">+ Registrar estudo feito fora</button>
    </div>

    {{-- Semana: constância de relance --}}
    <x-ui.section title="Sua semana">
        <x-ui.card class="p-4">
            <div class="grid grid-cols-7 gap-2 text-center">
                @foreach ($week->days() as $day)
                    @php
                        $isToday = $day->isSameDay($today);
                        $studyDay = $user->studiesOn($day);
                        $secs = $daily[$day->toDateString()] ?? 0;
                        $counted = $secs >= (int) $user->setting('streak_min') * 60;
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
            <p class="mt-4 flex items-center gap-2 text-sm text-ink-soft">
                <x-icon name="flame" class="size-4 text-marker-ink dark:text-marker" />
                Estude {{ $user->setting('streak_min') }} min ou mais por dia para manter sua sequência.
            </p>
        </x-ui.card>
    </x-ui.section>

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

    <x-ui.section title="Em andamento" :action="$courses->isNotEmpty() ? 'Ver todos' : null" :action-href="route('courses')">
        @if ($courses->isEmpty())
            <x-ui.empty icon="cap" title="Nenhum curso por aqui ainda"
                        text="Cadastre o que você estuda — graduação, pós, curso livre, idioma ou aquele livro — com disciplinas ou módulos e tópicos.">
                <x-ui.button :href="route('courses', ['novo' => 1])" icon="plus">Criar meu primeiro curso</x-ui.button>
            </x-ui.empty>
        @else
            <div class="grid gap-3 sm:grid-cols-2">
                @foreach ($courses as $item)
                    <x-ui.course-card :course="$item" :stats="$courseStats[$item->id]" wire:key="today-course-{{ $item->id }}" />
                @endforeach
            </div>
        @endif
    </x-ui.section>
</div>
