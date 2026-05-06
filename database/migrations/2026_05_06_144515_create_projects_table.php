<?php
// ╔══════════════════════════════════════════════════════════════════╗
// ║  2024_01_03_create_projects_table.php                            ║
// ╚══════════════════════════════════════════════════════════════════╝
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Projects ──────────────────────────────────────────────────────────
        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();           // PRJ-001
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'on_hold', 'completed', 'archived'])->default('active');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->foreignId('created_by')->constrained('users');  // Admin hoặc PM
            $table->timestamps();
        });

        // ── Project Members (pivot với vai trò) ──────────────────────────────
        // Đây là bảng trung tâm phân quyền: 1 user có thể là PM ở proj A, Dev ở proj B
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['pm', 'developer', 'tester']);
            $table->timestamp('joined_at')->useCurrent();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']); // 1 user 1 vai trò / project
        });

        // ── Stories ───────────────────────────────────────────────────────────
        Schema::create('stories', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique();           // STR-001
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');
            $table->enum('status', [
                'todo',
                'in_progress',
                'ready_to_review',
                'done',
            ])->default('todo');

            // Người tạo (PM), người được giao (Developer)
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('assigned_to')->nullable()->constrained('users');
            $table->foreignId('confirmed_by')->nullable()->constrained('users'); // PM xác nhận Done

            $table->timestamp('started_at')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('done_at')->nullable();
            $table->timestamps();
        });

        // ── Story status history (Traceability) ───────────────────────────────
        Schema::create('story_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('story_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('note')->nullable();
            $table->foreignId('changed_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('story_histories');
        Schema::dropIfExists('stories');
        Schema::dropIfExists('project_members');
        Schema::dropIfExists('projects');
    }
};
