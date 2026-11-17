<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithUser;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Mais')]
class More extends Component
{
    use WithUser;

    public function setTheme(string $theme): void
    {
        if (! in_array($theme, User::THEMES, true)) {
            return;
        }

        $this->user()->updateSettings(['theme' => $theme]);
        $this->dispatch('theme-changed', theme: $theme);
    }

    public function render(): View
    {
        return view('livewire.more', ['user' => $this->user()]);
    }
}
