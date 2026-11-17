<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\StudySession;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FormationController
{
    /** Download do certificado (o escopo por usuário faz o 404 para arquivos de outra pessoa). */
    public function certificate(Course $course): StreamedResponse
    {
        $cert = Certificate::where('course_id', $course->id)->firstOrFail();
        abort_unless(Storage::disk('local')->exists($cert->file_path), 404);

        return Storage::disk('local')->download($cert->file_path, $cert->original_name, ['Content-Type' => $cert->mime]);
    }

    /** CSV do histórico de formação (Excel em pt-BR: separador ";" e BOM UTF-8). */
    public function csv(): StreamedResponse
    {
        $hours = StudySession::query()->selectRaw('course_id, SUM(duration_seconds) as total')->groupBy('course_id')->pluck('total', 'course_id');
        $courses = Course::with('certificate')->orderByDesc('completed_on')->orderBy('position')->get();

        return response()->streamDownload(function () use ($courses, $hours) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Curso', 'Tipo', 'Instituição', 'Situação', 'Início', 'Conclusão', 'Carga horária (h)', 'Horas registradas no Estudy', 'Certificado'], ';');
            foreach ($courses as $c) {
                fputcsv($out, [
                    $c->name, $c->typeLabel(), $c->institution, $c->statusLabel(),
                    $c->started_on?->format('d/m/Y'), $c->completed_on?->format('d/m/Y'),
                    $c->workload_hours, number_format(((int) ($hours[$c->id] ?? 0)) / 3600, 1, ',', ''),
                    $c->certificate ? 'sim' : 'não',
                ], ';');
            }
            fclose($out);
        }, 'estudy-formacao-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
