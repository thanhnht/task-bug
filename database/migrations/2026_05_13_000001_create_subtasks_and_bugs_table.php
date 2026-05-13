<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Subtasks ──────────────────────────────────────────────────────────
        // Công việc con của Story, do Developer thực hiện
        Schema::create('subtasks', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                          // SUB-001
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['todo', 'in_progress', 'ready_to_review', 'done'])->default('todo');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        // ── Bugs ──────────────────────────────────────────────────────────────
        // Issue độc lập do Tester tạo ra từ Story, gán cho Developer xử lý
        Schema::create('bugs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();                          // BUG-001
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('steps_to_reproduce')->nullable();
            $table->enum('severity', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', ['open', 'in_progress', 'ready_to_review', 'closed'])->default('open');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');         // Tester báo cáo
            $table->unsignedSmallInteger('retest_count')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bugs');
        Schema::dropIfExists('subtasks');
    }
};
