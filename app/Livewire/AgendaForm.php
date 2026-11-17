<?php

namespace App\Livewire;

use App\Models\AgendaItem;
use App\Models\Course;
use App\Models\Subject;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/** Folha de prazo/avaliação. Abrir: $dispatch('edit-agenda', { id: null|3, courseId: null|1, subjectId: null|2 }) */
class AgendaForm extends Component
{
    public ?int $itemId = null;

    public ?int $courseId = null;

    public ?int $subjectId = null;

    public string $title = '';

    public string $type = 'exam';

    public string $dueOn = '';

    public string $dueTime = '';

    public bool $done = false;

    public ?string $grade = null;

    public ?string $maxGrade = '10';

    public string $weight = '1';

    public string $notes = '';

    #[On('edit-agenda')]
    public function open(?int $id = null, ?int $courseId = null, ?int $subjectId = null): void
    {
        $this->resetValidation();
        $this->reset();
        $this->dueOn = now()->addWeek()->toDateString();

        if ($id !== null) {
            $item = AgendaItem::findOrFail($id);
            $this->fill([
                'itemId' => $item->id, 'courseId' => $item->course_id, 'subjectId' => $item->subject_id,
                'title' => $item->title, 'type' => $item->type, 'dueOn' => $item->due_on->toDateString(),
                'dueTime' => (string) $item->due_time, 'done' => $item->isDone(),
                'grade' => $item->grade !== null ? rtrim(rtrim($item->grade, '0'), '.') : null,
                'maxGrade' => $item->max_grade !== null ? rtrim(rtrim($item->max_grade, '0'), '.') : null,
                'weight' => rtrim(rtrim($item->weight, '0'), '.'), 'notes' => (string) $item->notes,
            ]);
        } else {
            if ($subjectId !== null) {
                $subject = Subject::findOrFail($subjectId);
                $this->subjectId = $subject->id;
                $this->courseId = $subject->course_id;
            } else {
                $this->courseId = $courseId ?? Course::where('status', 'active')->ordered()->value('id');
            }
        }

        $this->dispatch('open-sheet', 'agenda-form');
    }

    public function updatedCourseId(): void
    {
        $this->subjectId = null;
    }

    public function save(): void
    {
        $data = $this->validate([
            'courseId' => ['required', 'integer'],
            'subjectId' => ['nullable', 'integer'],
            'title' => ['required', 'string', 'max:150'],
            'type' => ['required', Rule::in(array_keys(AgendaItem::TYPES))],
            'dueOn' => ['required', 'date'],
            'dueTime' => ['nullable', 'date_format:H:i'],
            'done' => ['boolean'],
            'grade' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'maxGrade' => ['nullable', 'numeric', 'gt:0', 'max:1000', 'required_with:grade'],
            'weight' => ['required', 'numeric', 'gt:0', 'max:100'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], [], ['courseId' => 'curso', 'title' => 'título', 'dueOn' => 'data', 'dueTime' => 'horário', 'grade' => 'nota', 'maxGrade' => 'nota máxima', 'weight' => 'peso']);

        if ($data['grade'] !== null && $data['grade'] !== '' && (float) $data['grade'] > (float) $data['maxGrade']) {
            $this->addError('grade', 'A nota não pode passar da nota máxima.');

            return;
        }

        $course = Course::findOrFail($data['courseId']);
        $subjectId = $data['subjectId'] ? Subject::where('course_id', $course->id)->findOrFail($data['subjectId'])->id : null;
        $hasGrade = $data['grade'] !== null && $data['grade'] !== '';

        $attributes = [
            'course_id' => $course->id,
            'subject_id' => $subjectId,
            'title' => $data['title'],
            'type' => $data['type'],
            'due_on' => $data['dueOn'],
            'due_time' => $data['dueTime'] ?: null,
            'status' => $data['done'] || $hasGrade ? 'done' : 'open',
            'grade' => $hasGrade ? $data['grade'] : null,
            'max_grade' => $data['maxGrade'] !== '' ? $data['maxGrade'] : null,
            'weight' => $data['weight'],
            'notes' => $data['notes'] ?: null,
        ];

        $this->itemId ? AgendaItem::findOrFail($this->itemId)->update($attributes) : AgendaItem::create($attributes);

        $this->dispatch('close-sheet', 'agenda-form');
        $this->dispatch('agenda-saved');
        $this->dispatch('toast', message: $this->itemId ? 'Atualizado.' : 'Adicionado à agenda.');
    }

    public function delete(): void
    {
        if ($this->itemId) {
            AgendaItem::findOrFail($this->itemId)->delete();
            $this->dispatch('close-sheet', 'agenda-form');
            $this->dispatch('agenda-saved');
            $this->dispatch('toast', message: 'Removido da agenda.');
        }
    }

    public function render(): View
    {
        return view('livewire.agenda-form', [
            'courses' => Course::query()->whereIn('status', ['active', 'planned', 'paused'])
                ->when($this->courseId, fn ($q) => $q->orWhere('id', $this->courseId))->ordered()->get(['id', 'name']),
            'subjects' => $this->courseId ? Subject::where('course_id', $this->courseId)->orderBy('position')->get(['id', 'name']) : collect(),
        ]);
    }
}
