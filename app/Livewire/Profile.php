<?php

namespace App\Livewire;

use App\Actions\Fortify\PasswordValidationRules;
use App\Livewire\Concerns\WithUser;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Perfil')]
class Profile extends Component
{
    use PasswordValidationRules;
    use WithUser;

    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $delete_password = '';

    public function mount(): void
    {
        $this->name = $this->user()->name;
        $this->email = $this->user()->email;
    }

    public function saveProfile(): void
    {
        $user = $this->user();

        $data = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)->ignore($user->id)],
        ]);

        $user->update($data);
        $this->dispatch('toast', message: 'Perfil atualizado.');
    }

    public function updatePassword(): void
    {
        $this->validate([
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ]);

        $this->user()->update(['password' => $this->password]);
        $this->reset('current_password', 'password', 'password_confirmation');
        $this->dispatch('close-sheet', 'password');
        $this->dispatch('toast', message: 'Senha alterada.');
    }

    public function deleteAccount(): void
    {
        $this->validate(['delete_password' => ['required', 'string', 'current_password:web']], attributes: ['delete_password' => 'senha']);

        $user = $this->user();
        Auth::guard('web')->logout();
        $user->delete();
        session()->invalidate();
        session()->regenerateToken();

        $this->redirectRoute('login');
    }

    public function render(): View
    {
        return view('livewire.profile', ['user' => $this->user()]);
    }
}
