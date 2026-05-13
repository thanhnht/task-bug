<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};

return new class extends Migration
{
    public function up(): void
    {
        // 1. Mở rộng enum để chứa cả hai giá trị
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('todo','in_progress','ready_to_review','ready_to_test','done') NOT NULL DEFAULT 'todo'");

        // 2. Cập nhật dữ liệu
        DB::table('tasks')->where('status', 'ready_to_review')->update(['status' => 'ready_to_test']);
        DB::table('task_histories')->where('from_status', 'ready_to_review')->update(['from_status' => 'ready_to_test']);
        DB::table('task_histories')->where('to_status',   'ready_to_review')->update(['to_status'   => 'ready_to_test']);

        // 3. Xoá giá trị cũ khỏi enum
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('todo','in_progress','ready_to_test','done') NOT NULL DEFAULT 'todo'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE tasks MODIFY status ENUM('todo','in_progress','ready_to_review','ready_to_test','done') NOT NULL DEFAULT 'todo'");

        DB::table('tasks')->where('status', 'ready_to_test')->update(['status' => 'ready_to_review']);
        DB::table('task_histories')->where('from_status', 'ready_to_test')->update(['from_status' => 'ready_to_review']);
        DB::table('task_histories')->where('to_status',   'ready_to_test')->update(['to_status'   => 'ready_to_review']);

        DB::statement("ALTER TABLE tasks MODIFY status ENUM('todo','in_progress','ready_to_review','done') NOT NULL DEFAULT 'todo'");
    }
};
