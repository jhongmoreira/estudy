@extends('reports.layout')
@php
    $h = fn (int $s) => \App\Support\Duration::human($s);
    $maxCourse = max(1, collect($byCourse)->max('value') ?? 1);
    $maxSubject = max(1, collect($bySubject)->max('value') ?? 1);
    $maxWeek = max(1, collect($byWeek)->max('total') ?? 1);
    $total = max(1, $summary['seconds']);
@endphp
@section('title', 'Relatório de estudos')
@section('heading')<span class="hl">Relatório de estudos</span>@endsection
@section('subheading'){{ $user->name }} · {{ $from->format('d/m/Y') }} a {{ $to->format('d/m/Y') }}@if ($course) · {{ $course->name }}@endif @endsection
@section('content')
    <table class="cards">
        <tr>
            <td><div class="label">Tempo estudado</div><div class="value">{{ $h($summary['seconds']) }}</div><div class="small muted">{{ $summary['sessions'] }} sessões</div></td>
            <td><div class="label">Dias com estudo</div><div class="value">{{ $summary['days'] }} de {{ $periodDays }}</div><div class="small muted">{{ round($summary['days'] / $periodDays * 100) }}% do período</div></td>
            <td><div class="label">Média por dia estudado</div><div class="value">{{ $h($summary['avg_per_day']) }}</div><div class="small muted">meta diária: {{ $h($goalSeconds) }}</div></td>
            <td><div class="label">Sequência atual</div><div class="value">{{ $streak['current'] }} dias</div><div class="small muted">recorde: {{ $streak['best'] }} dias</div></td>
        </tr>
    </table>

    @if ($summary['sessions'] === 0)
        <p class="muted" style="margin-top: 20px">Nenhuma sessão de estudo registrada neste período.</p>
    @else
        <table class="cols"><tr>
            <td>
                <h2>Por curso</h2>
                <table class="list">
                    @foreach ($byCourse as $c)
                        <tr>
                            <td style="width: 45%"><span class="dot" style="background: {{ $c['color'] }}"></span>{{ $c['name'] }}</td>
                            <td><div class="bar"><div style="width: {{ $c['value'] / $maxCourse * 100 }}%; background: {{ $c['color'] }}"></div></div></td>
                            <td class="right" style="width: 18%"><strong>{{ $h($c['value']) }}</strong></td>
                            <td class="right muted" style="width: 9%">{{ round($c['value'] / $total * 100) }}%</td>
                        </tr>
                    @endforeach
                </table>
            </td>
            <td>
                <h2>Por tipo de estudo</h2>
                <table class="list">
                    @foreach ($byKind as $k)
                        <tr><td>{{ $k['name'] }}</td><td class="right"><strong>{{ $h($k['value']) }}</strong></td><td class="right muted" style="width: 12%">{{ round($k['value'] / $total * 100) }}%</td></tr>
                    @endforeach
                </table>
                <h2>Período do dia</h2>
                <table class="list">
                    @foreach ($dayParts as $part => $sec)
                        <tr><td>{{ $part }}</td><td class="right">{{ $sec > 0 ? $h($sec) : '—' }}</td></tr>
                    @endforeach
                </table>
                <p class="small muted">Considera só sessões com horário (cronômetro ou horário informado).</p>
            </td>
        </tr></table>

        <h2>Disciplinas e módulos</h2>
        <table class="list">
            <tr><th>Disciplina / módulo</th><th>Curso</th><th style="width: 30%"></th><th class="right">Tempo</th></tr>
            @foreach ($bySubject as $s)
                <tr>
                    <td><span class="dot" style="background: {{ $s['color'] }}"></span>{{ $s['name'] }}</td>
                    <td class="muted">{{ $s['detail'] }}</td>
                    <td><div class="bar"><div style="width: {{ $s['value'] / $maxSubject * 100 }}%; background: {{ $s['color'] }}"></div></div></td>
                    <td class="right"><strong>{{ $h($s['value']) }}</strong></td>
                </tr>
            @endforeach
        </table>

        <div class="avoid">
            <h2>Semana a semana</h2>
            <table class="list">
                <tr><th style="width: 18%">Semana de</th><th></th><th class="right">Dias</th><th class="right">Tempo</th></tr>
                @foreach ($byWeek as $w)
                    <tr>
                        <td>{{ $w['label'] }}</td>
                        <td><div class="bar"><div style="width: {{ $w['total'] / $maxWeek * 100 }}%; background: #3047c9"></div></div></td>
                        <td class="right">{{ $w['days'] }}</td>
                        <td class="right"><strong>{{ $h($w['total']) }}</strong></td>
                    </tr>
                @endforeach
            </table>
        </div>

        <table class="cols avoid"><tr>
            <td>
                <h2>Exercícios</h2>
                @if ($questions['total'] > 0)
                    <p><strong style="font-size: 13px">{{ round($questions['correct'] / $questions['total'] * 100) }}%</strong> de acerto em {{ $questions['total'] }} questões</p>
                    <table class="list">
                        @foreach ($questions['by_subject'] as $q)
                            <tr><td>{{ $q['name'] }}</td><td class="right muted">{{ $q['correct'] }}/{{ $q['total'] }}</td><td class="right"><strong>{{ round($q['correct'] / max(1, $q['total']) * 100) }}%</strong></td></tr>
                        @endforeach
                    </table>
                @else
                    <p class="muted">Nenhuma questão registrada.</p>
                @endif
            </td>
            <td>
                <h2>Leitura e revisões</h2>
                <table class="list">
                    <tr><td>Páginas lidas</td><td class="right"><strong>{{ $reading['pages'] }}</strong>@if ($reading['per_hour']) <span class="muted">({{ str_replace('.', ',', $reading['per_hour']) }}/h)</span>@endif</td></tr>
                    <tr><td>Revisões feitas</td><td class="right"><strong>{{ $reviews['done'] }}</strong></td></tr>
                    <tr><td>Feitas em dia</td><td class="right">{{ $reviews['done'] > 0 ? round($reviews['on_time'] / $reviews['done'] * 100).'%' : '—' }}</td></tr>
                    @foreach (\App\Models\Review::RATINGS as $k => [$label])
                        <tr><td class="muted">{{ $label }}</td><td class="right">{{ $reviews['ratings'][$k] }}</td></tr>
                    @endforeach
                    <tr><td>Pendentes hoje (atrasadas)</td><td class="right">{{ $reviews['pending'] }} <span class="late">({{ $reviews['overdue'] }})</span></td></tr>
                </table>
            </td>
        </tr></table>

        @if ($sessions !== null)
            <h2 style="page-break-before: always">Sessões ({{ $sessions->count() }})</h2>
            <table class="list">
                <tr><th>Data</th><th>Início</th><th>Disciplina</th><th>Tópico</th><th>Tipo</th><th class="right">Questões</th><th class="right">Tempo</th></tr>
                @foreach ($sessions as $s)
                    <tr>
                        <td>{{ $s->date->format('d/m/Y') }}</td>
                        <td class="muted">{{ $s->started_at?->format('H:i') ?? '—' }}</td>
                        <td>{{ $s->subject->name }}</td>
                        <td class="muted">{{ $s->topic?->title }}</td>
                        <td class="muted">{{ $s->kindLabel() }}</td>
                        <td class="right">{{ $s->questions_total ? $s->questions_correct.'/'.$s->questions_total : '' }}</td>
                        <td class="right"><strong>{{ $h($s->duration_seconds) }}</strong></td>
                    </tr>
                @endforeach
                <tr class="total"><td colspan="6">Total</td><td class="right">{{ $h((int) $sessions->sum('duration_seconds')) }}</td></tr>
            </table>
        @endif
    @endif
@endsection
