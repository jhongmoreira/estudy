<?php

namespace App\Livewire;

use App\Models\Certificate;
use App\Models\Course;
use App\Models\StudySession;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

/** Histórico de formação: cursos concluídos, carga horária e certificados. */
#[Title('Formação')]
class Formation extends Component
{
    use WithFileUploads;

    #[Url(as: 'aba', except: 'concluidos')]
    public string $tab = 'concluidos';

    public ?int $uploadFor = null;

    public ?TemporaryUploadedFile $file = null;

    public function startUpload(int $courseId): void
    {
        $this->uploadFor = Course::findOrFail($courseId)->id;
        $this->reset('file');
        $this->resetValidation();
        $this->dispatch('open-sheet', 'certificate');
    }

    public function saveCertificate(): void
    {
        $this->validate(['file' => ['required', 'file', 'mimes:'.implode(',', Certificate::MIMES), 'max:'.Certificate::MAX_KB]],
            ['file.max' => 'O arquivo pode ter no máximo 5 MB.', 'file.mimes' => 'Envie um PDF ou imagem (JPG, PNG, WEBP).'], ['file' => 'arquivo']);

        $course = Course::findOrFail($this->uploadFor);
        $file = $this->file;
        $ext = strtolower($file->getClientOriginalExtension() ?: 'pdf');
        $path = $file->storeAs('certificates/'.$course->user_id, $course->id.'-'.Str::random(10).'.'.$ext, 'local');

        DB::transaction(function () use ($course, $path, $file) {
            $course->certificate?->delete();
            Certificate::create([
                'course_id' => $course->id,
                'file_path' => (string) $path,
                'original_name' => Str::limit($file->getClientOriginalName(), 200, ''),
                'mime' => (string) $file->getMimeType(),
                'size' => (int) $file->getSize(),
            ]);
        });

        $this->reset('file', 'uploadFor');
        $this->dispatch('close-sheet', 'certificate');
        $this->dispatch('toast', message: 'Certificado salvo.');
    }

    public function removeCertificate(int $courseId): void
    {
        Certificate::where('course_id', $courseId)->first()?->delete();
        $this->dispatch('toast', message: 'Certificado removido.');
    }

    public function render(): View
    {
        $statuses = $this->tab === 'andamento' ? ['active', 'paused'] : ['completed'];
        $courses = Course::with('certificate')->whereIn('status', $statuses)
            ->orderByDesc('completed_on')->orderBy('position')->get();

        $hours = StudySession::query()->selectRaw('course_id, SUM(duration_seconds) as total')->groupBy('course_id')->pluck('total', 'course_id');
        $completed = Course::where('status', 'completed')->get();
        $byType = $completed->groupBy('type')->map(fn ($g) => ['count' => $g->count(), 'hours' => (int) $g->sum(fn (Course $c) => $c->workload_hours ?? intdiv((int) ($hours[$c->id] ?? 0), 3600))]);
        $byYear = $completed->filter(fn (Course $c) => $c->completed_on !== null)->groupBy(fn (Course $c) => $c->completed_on?->year)
            ->map(fn ($g) => (int) $g->sum(fn (Course $c) => $c->workload_hours ?? intdiv((int) ($hours[$c->id] ?? 0), 3600)))->sortKeysDesc();

        return view('livewire.formation', [
            'courses' => $courses,
            'hours' => $hours,
            'byType' => $byType,
            'byYear' => $byYear,
            'totalHours' => (int) $byType->sum('hours'),
        ]);
    }
}
