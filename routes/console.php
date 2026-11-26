<?php

use App\Services\NotificationService;
use App\Services\TimerService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

/*
| Cron da Hostoo (uma única linha):
| * * * * * /usr/local/bin/ea-php84 /home/mv8f1v6j/public_html/estudy/artisan schedule:run >> /dev/null 2>&1
*/

// Aplica migrations/caches quando um novo release chega pela integração Git.
// Na VPS o deploy-app já roda o app:post-deploy a cada deploy (e grava .deployed-sha no release):
// lá não precisa subir um PHP a cada minuto só para isso. Na Hostoo continua igual.
if (! file_exists(base_path('.deployed-sha'))) {
    Schedule::command('app:post-deploy')
        ->everyMinute()
        ->withoutOverlapping();
}

// Hospedagem compartilhada não tem supervisor: processa a fila a cada minuto.
// Só sobe o queue:work quando há job esperando (economiza um PHP por minuto).
// Se a consulta falhar por qualquer motivo, roda como antes.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping()
    ->when(function () {
        try {
            return DB::table(config('queue.connections.database.table', 'jobs'))
                ->where('available_at', '<=', now()->getTimestamp())->exists();
        } catch (Throwable) {
            return true;
        }
    });

// Manutenção do SQLite: atualiza estatísticas (semanal) e compacta o arquivo (mensal).
Artisan::command('app:db-maintenance {--vacuum}', function () {
    DB::statement('PRAGMA optimize');
    if ($this->option('vacuum')) {
        DB::statement('VACUUM');
    }
    $this->info('Banco otimizado.');
})->purpose('PRAGMA optimize (e VACUUM com --vacuum)');

Schedule::command('app:db-maintenance')->weeklyOn(0, '04:00')->withoutOverlapping();
Schedule::command('app:db-maintenance --vacuum')->monthlyOn(1, '04:30')->withoutOverlapping();

// Avisos (push + central no app): lembrete diário, revisões, agenda e sequência. Cada aviso sai uma vez por dia.
Artisan::command('notifications:dispatch', function (NotificationService $service) {
    $this->info($service->dispatchDue().' aviso(s) enviado(s).');
})->purpose('Envia os avisos de estudo do horário atual');

Schedule::command('notifications:dispatch')->everyFifteenMinutes()->withoutOverlapping();

// Pomodoro com o app fechado: pausa ao fim do foco e avisa por push (fim do foco e fim da pausa).
Artisan::command('timers:pomodoro', function (TimerService $timers) {
    $this->info($timers->pomodoroTickAll().' aviso(s) de Pomodoro.');
})->purpose('Pausa/avisa os cronômetros em modo Pomodoro');

Schedule::command('timers:pomodoro')->everyMinute()->withoutOverlapping();
