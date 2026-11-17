<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('study_cycles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 80);
            $table->boolean('is_active')->default(false);
            $table->unsignedInteger('round')->default(1);
            $table->timestamp('round_started_at')->nullable();
            $table->timestamps();
        });

        Schema::create('cycle_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('study_cycle_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('target_min');
            $table->unsignedInteger('done_seconds')->default(0);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['study_cycle_id', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cycle_items');
        Schema::dropIfExists('study_cycles');
    }
};
