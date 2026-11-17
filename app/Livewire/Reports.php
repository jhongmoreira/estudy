<?php

namespace App\Livewire;

use App\Models\AgendaItem;
use App\Models\Course;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Escolha e download dos relatórios em PDF. */
#[Title('Relatórios')]
class Reports extends Component
{
    public string $from = '';

    public string $to = '';

    public ?int $studyCourse = null;

    public bool $withSessions = false;

    public ?int $gradesCourse = null;

    public ?int $progressCourse = null;

    public bool $withInProgress = false;

    public function mount(): void
    {
        $this->to = CarbonImmutable::today()->toDateString();
        $this->from = CarbonImmutable::today()->subDays(29)->toDateString();
        $this->gradesCourse = AgendaItem::whereNotNull('grade')->latest('due_on')->value('course_id')
            ?? Course::whereIn('type', ['undergrad', 'postgrad', 'masters', 'doctorate'])->where('status', 'active')->ordered()->value('id');
        $this->progressCourse = Course::where('status', 'active')->ordered()->value('id');
    }

    public function preset(string $which): void
    {
        $today = CarbonImmutable::today();
        [$from, $to] = match ($which) {
            'week' => [$today->startOfWeek(), $today],
            'month' => [$today->startOfMonth(), $today],
            'last_month' => [$today->subMonthNoOverflow()->startOfMonth(), $today->subMonthNoOverflow()->endOfMonth()],
            'semester' => [$today->month <= 6 ? $today->startOfYear() : $today->setDate($today->year, 7, 1), $today],
            'year' => [$today->startOfYear(), $today],
            default => [$today->subDays(29), $today],
        };
        $this->from = $from->toDateString();
        $this->to = $to->toDateString();
    }

    public function render(): View
    {
        return view('livewire.reports', [
            'courses' => Course::query()->ordered()->get(['id', 'name', 'status']),
            'studyUrl' => route('reports.study', array_filter([
                'de' => $this->from, 'ate' => $this->to, 'curso' => $this->studyCourse, 'sessoes' => $this->withSessions ? 1 : null,
            ])),
        ]);
    }
}
