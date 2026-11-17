<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Subject;
use App\Support\Palette;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/** Folha de criar/editar disciplina ou módulo. Abrir: $dispatch('edit-subject', { courseId: 1, id: null|5 }) */
class SubjectForm extends Component
{
    public ?int $courseId = null;

    public ?int $subjectId = null;

    public string $unit = 'Módulo';

    public bool $academic = false;

    public string $name = '';

    public ?string $color = null;

    public string $term = '';

    public string $status = 'active';

    public ?int $weekly_goal_hours = null;

    public ?string $passing_grade = null;

    public string $notes = '';

    #[On('edit-subject')]
    public function open(int $courseId, ?int $id = null): void
    {
        $this->resetValidation();
        $this->reset();

        $course = Course::findOrFail($courseId);
        $this->courseId = $course->id;
        $this->unit = $course->unitLabel();
        $this->academic = $course->isAcademic();

        if ($id !== null) {
            $subject = Subject::where('course_id', $course->id)->findOrFail($id);
            $this->subjectId = $subject->id;
            $this->fill([
                'name' => $subject->name,
                'color' => $subject->color,
                'term' => (string) $subject->term,
                'status' => $subject->status,
                'weekly_goal_hours' => $subject->weekly_goal_min !== null ? (int) round($subject->weekly_goal_min / 60) : null,
                'passing_grade' => $subject->passing_grade,
                'notes' => (string) $subject->notes,
            ]);
        } else {
            // Sugere o mesmo período da última disciplina cadastrada
            $this->term = (string) $course->subjects()->whereNotNull('term')->latest('id')->value('term');
        }

        $this->dispatch('open-sheet', 'subject-form');
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'color' => ['nullable', Rule::in(array_keys(Palette::COLORS))],
            'term' => ['nullable', 'string', 'max:20'],
            'status' => ['required', Rule::in(array_keys(Subject::STATUSES))],
            'weekly_goal_hours' => ['nullable', 'integer', 'min:1', 'max:100'],
            'passing_grade' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], attributes: ['name' => 'nome', 'term' => 'período', 'weekly_goal_hours' => 'meta semanal', 'passing_grade' => 'média para aprovação']);

        $data['weekly_goal_min'] = $data['weekly_goal_hours'] !== null ? $data['weekly_goal_hours'] * 60 : null;
        unset($data['weekly_goal_hours']);
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);

        $course = Course::findOrFail($this->courseId);

        if ($this->subjectId !== null) {
            Subject::where('course_id', $course->id)->findOrFail($this->subjectId)->update($data);
            $message = $this->unit === 'Disciplina' ? 'Disciplina atualizada.' : 'Módulo atualizado.';
        } else {
            $data['position'] = (int) $course->subjects()->max('position') + 1;
            $course->subjects()->create($data);
            $message = $this->unit === 'Disciplina' ? 'Disciplina criada.' : 'Módulo criado.';
        }

        $this->dispatch('close-sheet', 'subject-form');
        $this->dispatch('subject-saved');
        $this->dispatch('toast', message: $message);
    }

    public function render(): View
    {
        return view('livewire.subject-form');
    }
}
