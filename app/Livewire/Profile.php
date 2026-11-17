<?php

namespace App\Livewire;

use App\Actions\Fortify\PasswordValidationRules;
use App\Livewire\Concerns\WithUser;
use App\Models\User;
use App\Services\DataPortability;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

#[Title('Perfil')]
class Profile extends Component
{
    use PasswordValidationRules;
    use WithFileUploads;
    use WithUser;

    public string $name = '';

    public string $email = '';

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    public string $delete_password = '';

    public string $reset_password = '';

    public string $import_password = '';

    public ?TemporaryUploadedFile $backup = null;

    /** @var array<string, int> */
    public array $backupSummary = [];

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

    public function updatedBackup(DataPortability $portability): void
    {
        $this->backupSummary = [];
        $this->validate(['backup' => ['required', 'file', 'max:20480', 'mimetypes:application/json,text/plain']], ['backup.mimetypes' => 'Envie o arquivo .json exportado pelo Estudy.'], ['backup' => 'arquivo']);
        $this->backupSummary = $portability->summarize($this->readBackup());
    }

    public function importBackup(DataPortability $portability): void
    {
        $this->validate(['import_password' => ['required', 'string', 'current_password:web']], attributes: ['import_password' => 'senha']);
        $counts = $portability->import($this->user(), $this->readBackup());

        $this->reset('backup', 'backupSummary', 'import_password');
        session()->flash('toast', 'Backup importado: '.$counts['cursos'].' cursos e '.$counts['sessões'].' sessões.');
        $this->redirectRoute('home', navigate: true);
    }

    public function resetData(DataPortability $portability): void
    {
        $this->validate(['reset_password' => ['required', 'string', 'current_password:web']], attributes: ['reset_password' => 'senha']);
        $portability->reset($this->user());

        $this->reset('reset_password');
        session()->flash('toast', 'Pronto: seus dados de estudo foram apagados. Bora recomeçar!');
        $this->redirectRoute('home', navigate: true);
    }

    /** @return array<string, mixed> */
    private function readBackup(): array
    {
        $data = $this->backup ? json_decode((string) file_get_contents($this->backup->getRealPath()), true) : null;
        if (! is_array($data)) {
            throw ValidationException::withMessages(['backup' => 'Não foi possível ler o arquivo (JSON inválido).']);
        }

        return $data;
    }

    public function deleteAccount(): void
    {
        $this->validate(['delete_password' => ['required', 'string', 'current_password:web']], attributes: ['delete_password' => 'senha']);

        $user = $this->user();
        app(DataPortability::class)->reset($user); // remove também os arquivos de certificado
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
