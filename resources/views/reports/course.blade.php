@extends('reports.layout')
@php
    $h = fn (int $s) => \App\Support\Duration::human($s);
    $pct = fn (float $p) => (int) floor($p * 100).'%';
    $diff = [1 => '●', 2 => '●●', 3 => '●●●'];
@endphp
@section('title', $course->name)
@section('heading')<span class="hl">{{ $course->name }}</span>@endsection
@section('subheading'){{ $course->typeLabel() }}@if ($course->institution) · {{ $course->institution }}@endif · {{ $course->statusLabel() }}@if ($course->started_on) · desde {{ $course->started_on->format('m/Y') }}@endif @endsection
@section('content')
    <table class="cards">
        <tr>
            <td><div class="label">Progresso</div><div class="value">{{ $pct($courseStats['progress']) }}</div><div class="bar" style="margin-top: 4px"><div style="width: {{ $courseStats['progress'] * 100 }}%; background: {{ $course->color }}"></div></div></td>
            <td><div class="label">Tópicos concluídos</div><div class="value">{{ $courseStats['done'] }} / {{ $courseStats['total'] }}</div></td>
            <td><div class="label">Tempo estudado</div><div class="value">{{ $h($totalSeconds) }}</div>@if ($course->workload_hours)<div class="small muted">carga horária: {{ $course->workload_hours }} h</div>@endif</td>
            <td><div class="label">{{ $course->unitLabel(true) }}</div><div class="value">{{ $courseStats['subjects'] }}</div>@if ($course->target_on)<div class="small muted">previsão: {{ $course->target_on->format('d/m/Y') }}</div>@endif</td>
        </tr>
    </table>

    <h2>{{ $course->unitLabel(true) }}</h2>
    <table class="list">
        <tr><th>{{ $course->unitLabel() }}</th><th>Período</th><th style="width: 22%">Progresso</th><th class="right">Tópicos</th><th class="right">Tempo</th><th class="right">Último estudo</th></tr>
        @foreach ($subjects as $s)
            @php $st = $stats[$s->id]; $c = $s->color ?? $course->color; $t = $time->get($s->id); @endphp
            <tr>
                <td><span class="dot" style="background: {{ $c }}"></span><strong>{{ $s->name }}</strong>@if ($s->status !== 'active') <span class="muted">({{ mb_strtolower(\App\Models\Subject::STATUSES[$s->status] ?? '') }})</span>@endif</td>
                <td class="muted">{{ $s->term }}</td>
                <td><div class="bar"><div style="width: {{ $st['progress'] * 100 }}%; background: {{ $c }}"></div></div></td>
                <td class="right">{{ $st['done'] }}/{{ $st['total'] }}</td>
                <td class="right">{{ $t ? $h((int) $t->total) : '—' }}</td>
                <td class="right muted">{{ $t?->last ? \Carbon\CarbonImmutable::parse($t->last)->format('d/m/Y') : '—' }}</td>
            </tr>
        @endforeach
    </table>

    @if ($agenda->isNotEmpty())
        <h2>Próximos prazos</h2>
        <table class="list">
            @foreach ($agenda as $a)
                <tr><td style="width: 14%">{{ $a->due_on->format('d/m/Y') }}</td><td><strong>{{ $a->title }}</strong> <span class="muted">· {{ $a->typeLabel() }}@if ($a->subject) · {{ $a->subject->name }}@endif</span></td></tr>
            @endforeach
        </table>
    @endif

    <h2>Conteúdo</h2>
    @foreach ($subjects as $s)
        @if ($s->topics->isNotEmpty())
            <div class="avoid">
                <h3 class="stripe" style="border-color: {{ $s->color ?? $course->color }}">{{ $s->name }} <span class="muted small">· {{ $pct($stats[$s->id]['progress']) }}</span></h3>
                <table class="list">
                    @foreach ($s->topics as $t)
                        @php $rev = $reviews->get($t->id); @endphp
                        <tr>
                            <td style="width: 4%" class="{{ $t->isDone() ? 'done' : 'muted' }}">{{ $t->isDone() ? '✔' : ($t->status === 'doing' ? '◐' : '○') }}</td>
                            <td class="{{ $t->isDone() ? 'muted' : '' }}">{{ $t->title }}</td>
                            <td style="width: 9%" class="muted">{{ $diff[$t->difficulty] ?? '' }}</td>
                            <td style="width: 10%" class="right muted">{{ $t->estimated_min ? $h($t->estimated_min * 60) : '' }}</td>
                            <td style="width: 20%" class="right small {{ $rev && $rev->isOverdue() ? 'late' : 'muted' }}">{{ $rev ? 'revisar '.$rev->due_on->format('d/m') : ($t->done_on ? 'concluído '.$t->done_on->format('d/m') : '') }}</td>
                        </tr>
                    @endforeach
                </table>
            </div>
        @endif
    @endforeach
    <p class="small muted">✔ concluído · ◐ estudando · ○ a estudar · ● fácil · ●● médio · ●●● difícil</p>
@endsection
