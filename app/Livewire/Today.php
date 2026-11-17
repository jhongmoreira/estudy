<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithUser;
use App\Support\Week;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Hoje')]
class Today extends Component
{
    use WithUser;

    public function render(): View
    {
        $user = $this->user();
        $now = CarbonImmutable::now();
        $hour = (int) $now->format('G');

        $greeting = match (true) {
            $hour < 5 => 'Boa madrugada',
            $hour < 12 => 'Bom dia',
            $hour < 18 => 'Boa tarde',
            default => 'Boa noite',
        };

        return view('livewire.today', [
            'user' => $user,
            'greeting' => $greeting,
            'today' => $now,
            'goalMinutes' => $user->dailyGoalMinutes(),
            'todaySeconds' => 0, // sessões chegam na etapa 3
            'week' => Week::of($now, $user->weekStartsOn()),
        ]);
    }
}
