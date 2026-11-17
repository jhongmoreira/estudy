<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('type', 20);
            $table->string('institution', 120)->nullable();
            $table->string('color', 7);
            $table->string('emoji', 16)->nullable();
            $table->string('status', 12)->default('active');
            $table->date('started_on')->nullable();
            $table->date('target_on')->nullable();
            $table->date('completed_on')->nullable();
            $table->unsignedInteger('workload_hours')->nullable();
            $table->unsignedInteger('weekly_goal_min')->nullable();
            $table->string('url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['user_id', 'status', 'position']);
        });

        Schema::create('subjects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('color', 7)->nullable();
            $table->string('term', 20)->nullable();
            $table->string('status', 12)->default('active');
            $table->unsignedInteger('weekly_goal_min')->nullable();
            $table->decimal('passing_grade', 4, 2)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
            $table->index(['course_id', 'position']);
        });

        Schema::create('topics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->string('title', 200);
            $table->string('status', 8)->default('todo');
            $table->unsignedTinyInteger('difficulty')->nullable();
            $table->unsignedInteger('estimated_min')->nullable();
            $table->string('resource_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('position')->default(0);
            $table->date('done_on')->nullable();
            $table->timestamps();
            $table->index(['subject_id', 'position']);
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('topics');
        Schema::dropIfExists('subjects');
        Schema::dropIfExists('courses');
    }
};
