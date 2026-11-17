<?php

namespace App\Livewire;

use App\Models\Course;
use App\Services\GoalService;
use App\Services\ProgressService;
use App\Support\Reorder;
use App\Support\Week;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Cursos')]
class Courses extends Component
{
    public const TABS = [
        'active' => ['Em andamento', ['active']],
        'planned' => ['Planejados', ['planned']],
        'paused' => ['Pausados', ['paused']],
        'done' => ['Concluídos', ['completed', 'dropped']],
    ];

    #[Url(as: 'aba', except: 'active')]
    public string $tab = 'active';

    public bool $organizing = false;

    /** ?novo=1 abre o formulário direto (atalho da tela Hoje) */
    #[Url(as: 'novo')]
    public bool $new = false;

    public function mount(): void
    {
        if (! array_key_exists($this->tab, self::TABS)) {
            $this->tab = 'active';
        }
    }

    public function setTab(string $tab): void
    {
        $this->tab = array_key_exists($tab, self::TABS) ? $tab : 'active';
        $this->organizing = false;
    }

    public function move(int $id, int $direction): void
    {
        Reorder::move(Course::query()->whereIn('status', self::TABS[$this->tab][1])->ordered(), $id, $direction);
    }

    #[On('course-saved')]
    public function refreshList(): void {}

    public function render(ProgressService $progress, GoalService $goals): View
    {
        $courses = Course::query()->whereIn('status', self::TABS[$this->tab][1])->ordered()->get();
        $counts = Course::query()->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('livewire.courses', [
            'courses' => $courses,
            'stats' => $progress->forCourses($courses),
            'courseWeek' => $goals->weekByCourse(Week::of(now(), auth()->user()?->weekStartsOn() ?? 1)),
            'tabCounts' => collect(self::TABS)->map(fn ($t) => collect($t[1])->sum(fn ($s) => (int) ($counts[$s] ?? 0))),
        ]);
    }
}
