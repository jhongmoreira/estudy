<?php

namespace App\Livewire;

use App\Models\Course;
use App\Support\Palette;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

/** Folha de criar/editar curso. Abrir: $dispatch('edit-course', { id: null|123 }) */
class CourseForm extends Component
{
    public ?int $courseId = null;

    public string $name = '';

    public string $type = 'free';

    public string $institution = '';

    public string $color = '#3047c9';

    public string $emoji = '';

    public string $status = 'active';

    public ?string $started_on = null;

    public ?string $target_on = null;

    public ?string $completed_on = null;

    public ?int $workload_hours = null;

    public ?int $weekly_goal_hours = null;

    public string $url = '';

    public string $notes = '';

    #[On('edit-course')]
    public function open(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset();

        if ($id !== null) {
            $course = Course::findOrFail($id);
            $this->courseId = $course->id;
            $this->fill([
                'name' => $course->name,
                'type' => $course->type,
                'institution' => (string) $course->institution,
                'color' => $course->color,
                'emoji' => (string) $course->emoji,
                'status' => $course->status,
                'started_on' => $course->started_on?->toDateString(),
                'target_on' => $course->target_on?->toDateString(),
                'completed_on' => $course->completed_on?->toDateString(),
                'workload_hours' => $course->workload_hours,
                'weekly_goal_hours' => $course->weekly_goal_min !== null ? (int) round($course->weekly_goal_min / 60) : null,
                'url' => (string) $course->url,
                'notes' => (string) $course->notes,
            ]);
        } else {
            // Próxima cor da paleta que ainda não está em uso
            $used = Course::query()->pluck('color')->all();
            $this->color = collect(array_keys(Palette::COLORS))->first(fn ($c) => ! in_array($c, $used, true)) ?? '#3047c9';
        }

        $this->dispatch('open-sheet', 'course-form');
    }

    public function updatedStatus(string $value): void
    {
        if ($value === 'completed' && $this->completed_on === null) {
            $this->completed_on = now()->toDateString();
        }
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => ['required', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(Course::TYPES))],
            'institution' => ['nullable', 'string', 'max:120'],
            'color' => ['required', Rule::in(array_keys(Palette::COLORS))],
            'emoji' => ['nullable', 'string', 'max:16'],
            'status' => ['required', Rule::in(array_keys(Course::STATUSES))],
            'started_on' => ['nullable', 'date'],
            'target_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'completed_on' => ['nullable', 'date', Rule::requiredIf($this->status === 'completed')],
            'workload_hours' => ['nullable', 'integer', 'min:1', 'max:20000'],
            'weekly_goal_hours' => ['nullable', 'integer', 'min:1', 'max:100'],
            'url' => ['nullable', 'url', 'max:500'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ], attributes: [
            'name' => 'nome', 'type' => 'tipo', 'institution' => 'instituição', 'started_on' => 'início',
            'target_on' => 'previsão de término', 'completed_on' => 'data de conclusão', 'workload_hours' => 'carga horária',
            'weekly_goal_hours' => 'meta semanal', 'url' => 'link',
        ]);

        $data['weekly_goal_min'] = $data['weekly_goal_hours'] !== null ? $data['weekly_goal_hours'] * 60 : null;
        unset($data['weekly_goal_hours']);
        $data = array_map(fn ($v) => $v === '' ? null : $v, $data);
        if ($data['status'] !== 'completed') {
            $data['completed_on'] = null;
        }

        if ($this->courseId !== null) {
            Course::findOrFail($this->courseId)->update($data);
            $this->dispatch('close-sheet', 'course-form');
            $this->dispatch('course-saved');
            $this->dispatch('toast', message: 'Curso atualizado.');

            return;
        }

        $data['position'] = (int) Course::query()->max('position') + 1;
        $course = Course::create($data);

        session()->flash('toast', 'Curso criado! Agora adicione o conteúdo.');
        $this->redirectRoute('courses.show', $course, navigate: true);
    }

    public function render(): View
    {
        return view('livewire.course-form');
    }
}
