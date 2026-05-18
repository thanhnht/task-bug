<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Add review_approved to tasks.status enum ───────────────────────
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('todo','in_progress','ready_to_test','review_approved','done') NOT NULL DEFAULT 'todo'");

        // ── 2. Notifications table ────────────────────────────────────────────
        Schema::create('user_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('type', 60)->default('info');
            $table->string('title', 200);
            $table->text('body')->nullable();
            $table->string('url', 500)->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notifications');
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('todo','in_progress','ready_to_test','done') NOT NULL DEFAULT 'todo'");
    }
};
