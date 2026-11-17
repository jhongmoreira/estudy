@extends('reports.layout')
@php
    $n = fn ($v) => $v === null ? '—' : rtrim(rtrim(number_format((float) $v, 2, ',', ''), '0'), ',');
    $status = [
        'approved' => ['Aprovado', 'ok'], 'on_track' => ['Em dia', 'info'], 'at_risk' => ['Atenção', 'warn'],
        'impossible' => ['Abaixo da média', 'bad'], 'no_target' => ['Sem média mínima', 'info'], 'none' => ['Sem notas', 'info'],
    ];
@endphp
@section('title', 'Boletim')
@section('heading')<span class="hl">Boletim de notas</span>@endsection
@section('subheading'){{ $user->name }} · {{ $course->name }}@if ($course->institution) · {{ $course->institution }}@endif @endsection
@section('content')
    @if ($rows->isEmpty())
        <p class="muted" style="margin-top: 20px">Nenhuma avaliação cadastrada neste curso. Cadastre provas e trabalhos na Agenda do Estudy para gerar o boletim.</p>
    @else
        <h2>Resumo por disciplina</h2>
        <table class="list">
            <tr><th>Disciplina</th><th>Período</th><th class="center">Avaliações</th><th class="right">Média atual</th><th class="right">Mínima</th><th class="right">Precisa (no que falta)</th><th class="center">Situação</th></tr>
            @foreach ($rows as $r)
                @php [$label, $cls] = $status[$r['summary']['status']] ?? ['—', 'info']; @endphp
                <tr>
                    <td><span class="dot" style="background: {{ $r['subject']->color ?? $course->color }}"></span><strong>{{ $r['subject']->name }}</strong></td>
                    <td class="muted">{{ $r['subject']->term }}</td>
                    <td class="center">{{ $r['items']->whereNotNull('grade')->count() }}/{{ $r['items']->count() }}</td>
                    <td class="right"><strong>{{ $n($r['summary']['average']) }}</strong></td>
                    <td class="right muted">{{ $n($r['subject']->passing_grade) }}</td>
                    <td class="right">{{ in_array($r['summary']['status'], ['on_track', 'at_risk'], true) ? $n($r['summary']['needed']) : '—' }}</td>
                    <td class="center"><span class="tag {{ $cls }}">{{ $label }}</span></td>
                </tr>
            @endforeach
        </table>
        <p class="small muted">Notas convertidas para a escala 0–10 e ponderadas pelo peso de cada avaliação. "Precisa" = média necessária nas avaliações ainda sem nota para atingir a mínima.</p>

        @foreach ($rows as $r)
            @if ($r['items']->isNotEmpty())
                <div class="avoid">
                    <h3 class="stripe" style="border-color: {{ $r['subject']->color ?? $course->color }}">{{ $r['subject']->name }}</h3>
                    <table class="list">
                        <tr><th>Avaliação</th><th>Tipo</th><th>Data</th><th class="right">Peso</th><th class="right">Nota</th><th class="right">Em 0–10</th></tr>
                        @foreach ($r['items'] as $item)
                            <tr>
                                <td>{{ $item->title }}</td>
                                <td class="muted">{{ $item->typeLabel() }}</td>
                                <td>{{ $item->due_on->format('d/m/Y') }}</td>
                                <td class="right">{{ $n($item->weight) }}</td>
                                <td class="right">{{ $item->grade !== null ? $n($item->grade).' / '.$n($item->max_grade ?? 10) : ($item->isDone() ? 'aguardando' : 'pendente') }}</td>
                                <td class="right"><strong>{{ $n($item->normalizedGrade()) }}</strong></td>
                            </tr>
                        @endforeach
                        <tr class="total"><td colspan="5">Média ponderada</td><td class="right">{{ $n($r['summary']['average']) }}</td></tr>
                    </table>
                </div>
            @endif
        @endforeach
    @endif
@endsection
