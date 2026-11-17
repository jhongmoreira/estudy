<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->timestamp('started_at')->nullable();
            $table->unsignedInteger('duration_seconds');
            $table->string('kind', 12)->default('theory');
            $table->unsignedInteger('pages')->nullable();
            $table->unsignedInteger('questions_total')->nullable();
            $table->unsignedInteger('questions_correct')->nullable();
            $table->string('source', 8)->default('manual');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'date']);
            $table->index(['user_id', 'subject_id', 'date']);
        });

        // Um cronômetro por usuário: o estado fica no servidor (sobrevive a recarregar e trocar de aparelho)
        Schema::create('active_timers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('kind', 12)->default('theory');
            $table->boolean('pomodoro')->default(false);
            $table->timestamp('started_at');
            $table->timestamp('paused_at')->nullable();
            $table->unsignedInteger('paused_seconds')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('active_timers');
        Schema::dropIfExists('study_sessions');
    }
};
