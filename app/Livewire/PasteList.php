<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Subject;
use App\Services\ListImporter;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/** Folha "Colar lista". Abrir: $dispatch('paste-list', { courseId: 1, subjectId: null|5 }) */
class PasteList extends Component
{
    public ?int $courseId = null;

    public ?int $subjectId = null;

    public string $text = '';

    public string $target = '';

    public string $unitPlural = 'módulos';

    #[On('paste-list')]
    public function open(int $courseId, ?int $subjectId = null): void
    {
        $this->reset();
        $course = Course::findOrFail($courseId);
        $this->courseId = $course->id;
        $this->unitPlural = mb_strtolower($course->unitLabel(true));

        if ($subjectId !== null) {
            $subject = Subject::where('course_id', $course->id)->findOrFail($subjectId);
            $this->subjectId = $subject->id;
            $this->target = $subject->name;
        } else {
            $this->target = $course->name;
        }

        $this->dispatch('open-sheet', 'paste-list');
    }

    public function save(ListImporter $importer): void
    {
        $this->validate(['text' => ['required', 'string', 'max:50000']], attributes: ['text' => 'lista']);

        $course = Course::findOrFail($this->courseId);
        $subject = $this->subjectId ? Subject::where('course_id', $course->id)->findOrFail($this->subjectId) : null;
        $groups = $importer->parse($this->text, $subject === null);

        if ($groups === []) {
            $this->addError('text', 'Não encontramos nenhum item na lista.');

            return;
        }

        $result = $importer->store($groups, $course, $subject);

        $this->dispatch('close-sheet', 'paste-list');
        $this->dispatch('subject-saved');
        $this->dispatch('toast', message: $this->summary($result['subjects'], $result['topics']).' adicionados.');
        $this->reset('text');
    }

    private function summary(int $subjects, int $topics): string
    {
        $parts = [];
        if ($subjects > 0) {
            $parts[] = $subjects.' '.($subjects === 1 ? rtrim($this->unitPlural, 's') : $this->unitPlural);
        }
        $parts[] = $topics.' '.($topics === 1 ? 'tópico' : 'tópicos');

        return implode(' e ', $parts);
    }

    public function render(ListImporter $importer): View
    {
        $preview = trim($this->text) === '' ? [] : $importer->parse($this->text, $this->subjectId === null);

        return view('livewire.paste-list', [
            'preview' => $preview,
            'topicCount' => array_sum(array_map(fn ($g) => count($g['topics']), $preview)),
            'subjectCount' => count(array_filter($preview, fn ($g) => $g['subject'] !== null)),
        ]);
    }
}
