<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('topic_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('origin_session_id')->nullable()->constrained('study_sessions')->nullOnDelete();
            $table->foreignId('session_id')->nullable()->constrained('study_sessions')->nullOnDelete();
            $table->unsignedTinyInteger('step')->default(1);
            $table->date('due_on');
            $table->string('status', 8)->default('pending');
            $table->string('rating', 8)->nullable();
            $table->date('done_on')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'due_on']);
            $table->index(['topic_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
