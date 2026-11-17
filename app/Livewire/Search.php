<?php

namespace App\Livewire;

use App\Models\Course;
use App\Models\Subject;
use App\Models\Topic;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Buscar')]
class Search extends Component
{
    #[Url(as: 'q', except: '')]
    public string $q = '';

    public function render(): View
    {
        $term = trim($this->q);
        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';
        $has = mb_strlen($term) >= 2;

        return view('livewire.search', [
            'courses' => $has ? Course::where(fn ($w) => $w->where('name', 'like', $like)->orWhere('institution', 'like', $like))->ordered()->limit(10)->get() : collect(),
            'subjects' => $has ? Subject::with('course')->where('name', 'like', $like)->limit(20)->get() : collect(),
            'topics' => $has ? Topic::with('subject.course')->where('title', 'like', $like)->limit(30)->get() : collect(),
            'searched' => $has,
        ]);
    }
}
