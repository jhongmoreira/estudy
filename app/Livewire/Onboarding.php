<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Boas-vindas')]
#[Layout('components.layouts.focus')]
class Onboarding extends Component
{
    use WithUser;

    public int $step = 1;

    public int $daily_goal_min = 60;

    /** @var list<int> */
    public array $study_days = [1, 2, 3, 4, 5, 6];

    public function mount(): void
    {
        if ($this->user()->onboarded_at !== null) {
            $this->redirectRoute('home', navigate: true);
        }
    }

    public function setGoal(int $minutes): void
    {
        $this->daily_goal_min = $minutes;
    }

    public function toggleDay(int $day): void
    {
        $days = collect($this->study_days);
        $this->study_days = ($days->contains($day) ? $days->reject(fn ($d) => $d === $day) : $days->push($day))
            ->map(fn ($d) => (int) $d)->sort()->values()->all();
    }

    public function next(): void
    {
        $this->validate(['daily_goal_min' => ['required', 'integer', 'min:5', 'max:720']], attributes: ['daily_goal_min' => 'meta diária']);
        $this->step = 2;
    }

    public function back(): void
    {
        $this->step = 1;
    }

    public function finish(): void
    {
        $this->validate([
            'daily_goal_min' => ['required', 'integer', 'min:5', 'max:720'],
            'study_days' => ['required', 'array', 'min:1'],
            'study_days.*' => ['integer', 'between:0,6'],
        ], ['study_days.min' => 'Escolha pelo menos um dia.', 'study_days.required' => 'Escolha pelo menos um dia.']);

        $this->complete([
            'daily_goal_min' => $this->daily_goal_min,
            'streak_min' => min(15, $this->daily_goal_min),
            'study_days' => $this->study_days,
        ]);
    }

    public function skip(): void
    {
        $this->complete([]);
    }

    /** @param  array<string, mixed>  $settings */
    private function complete(array $settings): void
    {
        $user = $this->user();
        $user->updateSettings($settings);
        $user->update(['onboarded_at' => now()]);

        session()->flash('toast', 'Tudo pronto! Bons estudos.');
        $this->redirectRoute('home', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.onboarding');
    }
}
