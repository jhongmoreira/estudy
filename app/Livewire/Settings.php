<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithUser;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Configurações')]
class Settings extends Component
{
    use WithUser;

    public int $daily_goal_min = 60;

    public int $streak_min = 15;

    /** @var list<int> */
    public array $study_days = [];

    public int $week_starts_on = 1;

    /** @var array{focus: int, short: int, long: int, every: int} */
    public array $pomodoro = ['focus' => 25, 'short' => 5, 'long' => 15, 'every' => 4];

    public function mount(): void
    {
        $user = $this->user();
        $this->daily_goal_min = $user->dailyGoalMinutes();
        $this->streak_min = (int) $user->setting('streak_min');
        $this->study_days = $user->studyDays();
        $this->week_starts_on = $user->weekStartsOn();
        $this->pomodoro = $user->pomodoro();
    }

    public function toggleDay(int $day): void
    {
        $days = collect($this->study_days);
        $this->study_days = ($days->contains($day) ? $days->reject(fn ($d) => $d === $day) : $days->push($day))
            ->map(fn ($d) => (int) $d)->sort()->values()->all();
    }

    public function save(): void
    {
        $data = $this->validate([
            'daily_goal_min' => ['required', 'integer', 'min:5', 'max:720'],
            'streak_min' => ['required', 'integer', 'min:1', 'lte:daily_goal_min'],
            'study_days' => ['required', 'array', 'min:1'],
            'study_days.*' => ['integer', 'between:0,6'],
            'week_starts_on' => ['required', 'in:0,1'],
            'pomodoro.focus' => ['required', 'integer', 'min:5', 'max:120'],
            'pomodoro.short' => ['required', 'integer', 'min:1', 'max:30'],
            'pomodoro.long' => ['required', 'integer', 'min:5', 'max:60'],
            'pomodoro.every' => ['required', 'integer', 'min:2', 'max:8'],
        ], attributes: [
            'daily_goal_min' => 'meta diária',
            'streak_min' => 'mínimo para a sequência',
            'study_days' => 'dias de estudo',
            'pomodoro.focus' => 'foco', 'pomodoro.short' => 'pausa curta', 'pomodoro.long' => 'pausa longa', 'pomodoro.every' => 'pausa longa a cada',
        ]);

        $data['pomodoro'] = array_map('intval', $data['pomodoro']);
        $this->user()->updateSettings($data);
        $this->dispatch('toast', message: 'Configurações salvas.');
    }

    public function render(): View
    {
        return view('livewire.settings');
    }
}
