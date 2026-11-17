<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\User;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/** Relatórios em PDF (dompdf, gerados no servidor). */
class ReportController
{
    public function __construct(private ReportService $reports) {}

    public function study(Request $request): Response
    {
        $data = $request->validate([
            'de' => ['nullable', 'date'],
            'ate' => ['nullable', 'date', 'after_or_equal:de'],
            'curso' => ['nullable', 'integer'],
            'sessoes' => ['nullable', 'boolean'],
        ]);
        $to = isset($data['ate']) ? CarbonImmutable::parse($data['ate']) : CarbonImmutable::today();
        $from = isset($data['de']) ? CarbonImmutable::parse($data['de']) : $to->subDays(29);
        if ($from->diffInDays($to) > 1100) {
            $from = $to->subDays(1100);
        }
        $course = isset($data['curso']) ? Course::findOrFail($data['curso']) : null;

        return $this->pdf('reports.study', $this->reports->study($this->user($request), $from, $to, $course, (bool) ($data['sessoes'] ?? false)),
            'estudy-estudos-'.$from->format('Ymd').'-'.$to->format('Ymd'));
    }

    public function grades(Request $request, Course $course): Response
    {
        return $this->pdf('reports.grades', $this->reports->grades($this->user($request), $course), 'estudy-boletim-'.str($course->name)->slug());
    }

    public function course(Request $request, Course $course): Response
    {
        return $this->pdf('reports.course', $this->reports->course($this->user($request), $course), 'estudy-curso-'.str($course->name)->slug());
    }

    public function formation(Request $request): Response
    {
        return $this->pdf('reports.formation', $this->reports->formation($this->user($request), $request->boolean('andamento')), 'estudy-formacao-'.now()->format('Ymd'));
    }

    /** @param  array<string, mixed>  $data */
    private function pdf(string $view, array $data, string $name): Response
    {
        $pdf = Pdf::loadView($view, $data)->setPaper('a4')->setOption('isFontSubsettingEnabled', true);

        return $pdf->download($name.'.pdf');
    }

    private function user(Request $request): User
    {
        /** @var User */
        return $request->user();
    }
}
