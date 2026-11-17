<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Subject;
use App\Services\ProgressService;
use App\Support\Reorder;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CourseShow extends Component
{
    public Course $course;

    public bool $organizing = false;

    public string $confirmName = '';

    public function move(int $id, int $direction): void
    {
        Reorder::move(Subject::query()->where('course_id', $this->course->id)->orderBy('position')->orderBy('id'), $id, $direction);
    }

    public function deleteCourse(): void
    {
        if (trim(mb_strtolower($this->confirmName)) !== trim(mb_strtolower($this->course->name))) {
            $this->addError('confirmName', 'Digite o nome do curso exatamente como aparece.');

            return;
        }

        $this->course->delete();
        session()->flash('toast', 'Curso excluído.');
        $this->redirectRoute('courses', navigate: true);
    }

    #[On('course-saved')]
    #[On('subject-saved')]
    public function refreshCourse(): void
    {
        $this->course->refresh();
    }

    public function render(ProgressService $progress): View
    {
        $subjects = $this->course->subjects()->get();
        $stats = $progress->forSubjects($subjects->pluck('id')->all());

        return view('livewire.course-show', [
            'subjects' => $subjects,
            'groups' => $subjects->groupBy(fn (Subject $s) => $s->term ?? ''),
            'stats' => $stats,
            'courseStats' => $progress->forCourse($this->course),
        ])->title($this->course->name);
    }
}
