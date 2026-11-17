<?php

namespace App\Livewire;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Cursos')]
class Courses extends Component
{
    public function render(): View
    {
        return view('livewire.courses');
    }
}
