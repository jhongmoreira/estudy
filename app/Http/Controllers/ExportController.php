<?php

namespace App\Http\Controllers;

use App\Models\StudySession;
use App\Models\User;
use App\Services\DataPortability;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController
{
    /** Backup completo em JSON. */
    public function backup(Request $request, DataPortability $portability): StreamedResponse
    {
        /** @var User $user */
        $user = $request->user();
        $json = (string) json_encode($portability->export($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        return response()->streamDownload(fn () => print ($json), 'estudy-backup-'.now()->format('Y-m-d').'.json', ['Content-Type' => 'application/json']);
    }

    /** Sessões em CSV (Excel pt-BR: ";" e BOM). Aceita ?de=AAAA-MM-DD&ate=AAAA-MM-DD. */
    public function sessions(Request $request): StreamedResponse
    {
        $from = $request->date('de') ? CarbonImmutable::parse($request->date('de')) : null;
        $to = $request->date('ate') ? CarbonImmutable::parse($request->date('ate')) : null;

        $sessions = StudySession::with(['course', 'subject', 'topic'])
            ->when($from, fn ($q) => $q->where('date', '>=', $from->toDateString()))
            ->when($to, fn ($q) => $q->where('date', '<=', $to->toDateString()))
            ->orderBy('date')->orderBy('started_at')->get();

        return response()->streamDownload(function () use ($sessions) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Data', 'Início', 'Minutos', 'Curso', 'Disciplina/módulo', 'Tópico', 'Tipo', 'Páginas', 'Questões', 'Acertos', 'Origem', 'Anotações'], ';');
            foreach ($sessions as $s) {
                fputcsv($out, [
                    $s->date->format('d/m/Y'), $s->started_at?->format('H:i'), (int) round($s->duration_seconds / 60),
                    $s->course->name, $s->subject->name, $s->topic?->title, $s->kindLabel(),
                    $s->pages, $s->questions_total, $s->questions_correct, $s->source === 'timer' ? 'cronômetro' : 'manual', $s->notes,
                ], ';');
            }
            fclose($out);
        }, 'estudy-sessoes-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
