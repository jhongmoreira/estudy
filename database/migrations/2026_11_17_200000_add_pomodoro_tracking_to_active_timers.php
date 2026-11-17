<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('active_timers', function (Blueprint $table) {
            // Último bloco de foco já encerrado (pausado) e se o fim da pausa já foi avisado
            $table->unsignedInteger('pomodoro_block')->default(0)->after('pomodoro');
            $table->timestamp('break_alerted_at')->nullable()->after('pomodoro_block');
        });
    }

    public function down(): void
    {
        Schema::table('active_timers', function (Blueprint $table) {
            $table->dropColumn(['pomodoro_block', 'break_alerted_at']);
        });
    }
};
