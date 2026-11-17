<?php

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

/*
| Cron da Hostoo (uma única linha):
| * * * * * /usr/local/bin/ea-php84 /home/mv8f1v6j/public_html/estudy/artisan schedule:run >> /dev/null 2>&1
*/

// Aplica migrations/caches quando um novo release chega pela integração Git.
Schedule::command('app:post-deploy')
    ->everyMinute()
    ->withoutOverlapping();

// Hospedagem compartilhada não tem supervisor: processa a fila a cada minuto.
Schedule::command('queue:work --stop-when-empty --max-time=50 --tries=3')
    ->everyMinute()
    ->withoutOverlapping();

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
