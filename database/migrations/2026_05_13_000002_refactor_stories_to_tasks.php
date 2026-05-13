<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Dọn sạch bảng cũ (FK-safe order) ────────────────────────────────
        Schema::dropIfExists('bugs');
        Schema::dropIfExists('subtasks');
        Schema::dropIfExists('story_histories');
        Schema::dropIfExists('stories');

        // ── Tasks: entity thống nhất thay thế Story + Subtask + Bug ──────────
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                          // TSK-001
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();

            // Self-reference: null = task chính, có giá trị = task con
            $table->foreignId('parent_id')
                  ->nullable()
                  ->constrained('tasks')
                  ->nullOnDelete();

            // task | subtask | bug | research | fix | test
            $table->enum('type', ['task', 'subtask', 'bug', 'research', 'fix', 'test'])
                  ->default('task');

            $table->string('title');
            $table->text('description')->nullable();

            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['todo', 'in_progress', 'ready_to_review', 'done'])->default('todo');

            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('done_at')->nullable();

            $table->timestamps();
        });

        // ── Task histories: audit trail ───────────────────────────────────────
        Schema::create('task_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_histories');
        Schema::dropIfExists('tasks');
    }
};
