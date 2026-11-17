<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title', 150);
            $table->string('type', 14)->default('exam');
            $table->date('due_on');
            $table->string('due_time', 5)->nullable();
            $table->string('status', 6)->default('open');
            $table->decimal('grade', 6, 2)->nullable();
            $table->decimal('max_grade', 6, 2)->nullable();
            $table->decimal('weight', 5, 2)->default(1);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'status', 'due_on']);
            $table->index(['subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_items');
    }
};
