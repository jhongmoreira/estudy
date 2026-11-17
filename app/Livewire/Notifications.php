<?php

namespace App\Livewire;

use App\Livewire\Concerns\WithUser;
use App\Models\User;
use App\Notifications\StudyAlert;
use App\Support\VapidKeys;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Title;
use Livewire\Component;
use NotificationChannels\WebPush\PushSubscription;

#[Title('Notificações')]
class Notifications extends Component
{
    use WithUser;

    /** @var array<string, bool> */
    public array $prefs = [];

    public string $reminderTime = '19:00';

    public function mount(): void
    {
        $user = $this->user();
        foreach (array_keys(User::NOTIFICATION_TYPES) as $type) {
            $this->prefs[$type] = $user->wantsNotification($type);
        }
        $this->reminderTime = (string) ($user->setting('reminder_time') ?? '19:00');
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function updatedPrefs(): void
    {
        $values = [];
        foreach (array_keys(User::NOTIFICATION_TYPES) as $type) {
            $values['notifications.'.$type] = (bool) ($this->prefs[$type] ?? true);
        }
        $this->user()->updateSettings($values);
        $this->dispatch('toast', message: 'Preferências salvas.');
    }

    public function updatedReminderTime(): void
    {
        $this->validate(['reminderTime' => ['required', 'date_format:H:i']], attributes: ['reminderTime' => 'horário']);
        $this->user()->updateSettings(['reminder_time' => $this->reminderTime]);
        $this->dispatch('toast', message: 'Lembrete às '.$this->reminderTime.'.');
    }

    public function sendTest(): void
    {
        $this->user()->notifyNow(new StudyAlert('Teste do Estudy', 'Tudo certo! Seus lembretes de estudo chegam por aqui.', route('notifications', absolute: false), 'bell', 'test'));
        $this->dispatch('toast', message: 'Aviso de teste enviado.');
    }

    public function removeDevice(int $id): void
    {
        $this->user()->pushSubscriptions()->whereKey($id)->delete();
        $this->dispatch('toast', message: 'Aparelho removido.');
    }

    public function clearAll(): void
    {
        $this->user()->notifications()->delete();
    }

    public function render(): View
    {
        $user = $this->user();

        return view('livewire.notifications', [
            'publicKey' => VapidKeys::publicKey(),
            'devices' => $user->pushSubscriptions()->latest()->get()->map(fn (PushSubscription $s) => [
                'id' => $s->id,
                'name' => $this->deviceName((string) $s->endpoint),
                'since' => CarbonImmutable::parse((string) $s->getAttribute('created_at'))->format('d/m/Y'),
            ]),
            'items' => $user->notifications()->latest()->limit(30)->get(),
        ]);
    }

    private function deviceName(string $endpoint): string
    {
        $host = (string) parse_url($endpoint, PHP_URL_HOST);

        return match (true) {
            str_contains($host, 'apple') => 'iPhone / Mac (Safari)',
            str_contains($host, 'google') || str_contains($host, 'fcm') => 'Android / Chrome',
            str_contains($host, 'mozilla') => 'Firefox',
            str_contains($host, 'windows') || str_contains($host, 'microsoft') => 'Edge / Windows',
            default => $host ?: 'Aparelho',
        };
    }
}
