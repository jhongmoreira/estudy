@extends('reports.layout')
@section('title', 'Histórico de formação')
@section('heading')<span class="hl">Histórico de formação</span>@endsection
@section('subheading'){{ $user->name }} · {{ $user->email }}@endsection
@section('content')
    <table class="cards">
        <tr>
            <td><div class="label">Cursos concluídos</div><div class="value">{{ $courses->where('status', 'completed')->count() }}</div></td>
            <td><div class="label">Horas (concluídos)</div><div class="value">{{ $totalHours }} h</div></td>
            <td><div class="label">Horas registradas no Estudy</div><div class="value">{{ $loggedHours }} h</div><div class="small muted">todos os cursos</div></td>
            <td><div class="label">Com certificado</div><div class="value">{{ $courses->filter(fn ($c) => $c->certificate)->count() }}</div></td>
        </tr>
    </table>

    @if ($courses->isEmpty())
        <p class="muted" style="margin-top: 20px">Nenhum curso concluído ainda.</p>
    @else
        <h2>Cursos</h2>
        <table class="list">
            <tr><th>Curso</th><th>Tipo</th><th>Instituição</th><th>Período</th><th class="right">Carga horária</th><th class="right">Registrado</th><th class="center">Certificado</th></tr>
            @foreach ($courses as $c)
                <tr>
                    <td><span class="dot" style="background: {{ $c->color }}"></span><strong>{{ $c->name }}</strong>@if ($c->status !== 'completed') <span class="tag info">{{ mb_strtolower($c->statusLabel()) }}</span>@endif</td>
                    <td class="muted">{{ $c->typeLabel() }}</td>
                    <td class="muted">{{ $c->institution }}</td>
                    <td>{{ $c->started_on?->format('m/Y') ?? '?' }} – {{ $c->completed_on?->format('m/Y') ?? 'atual' }}</td>
                    <td class="right">{{ $c->workload_hours ? $c->workload_hours.' h' : '—' }}</td>
                    <td class="right muted">{{ intdiv((int) ($hours[$c->id] ?? 0), 3600) }} h</td>
                    <td class="center">{{ $c->certificate ? 'sim' : '—' }}</td>
                </tr>
            @endforeach
            <tr class="total"><td colspan="4">Total dos concluídos</td><td class="right">{{ $totalHours }} h</td><td colspan="2"></td></tr>
        </table>

        <table class="cols avoid"><tr>
            <td>
                <h2>Por tipo</h2>
                <table class="list">
                    @foreach ($byType as $type => $t)
                        <tr><td>{{ \App\Models\Course::TYPES[$type][0] ?? $type }}</td><td class="right">{{ $t['count'] }} {{ $t['count'] === 1 ? 'curso' : 'cursos' }}</td><td class="right"><strong>{{ $t['hours'] }} h</strong></td></tr>
                    @endforeach
                </table>
            </td>
            <td>
                <h2>Por ano de conclusão</h2>
                <table class="list">
                    @foreach ($byYear as $year => $hrs)
                        <tr><td>{{ $year }}</td><td class="right"><strong>{{ $hrs }} h</strong></td></tr>
                    @endforeach
                </table>
            </td>
        </tr></table>
        <p class="small muted">Horas dos concluídos: carga horária informada (do certificado); quando não há, usa as horas registradas no Estudy. Documento gerado pelo próprio estudante, sem validade institucional.</p>
    @endif
@endsection
