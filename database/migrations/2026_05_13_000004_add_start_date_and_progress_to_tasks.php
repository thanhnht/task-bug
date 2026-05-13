<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->date('start_date')->nullable()->after('status')
                  ->comment('Ngày bắt đầu dự kiến');
            $table->unsignedTinyInteger('manual_progress')->nullable()->after('estimated_hours')
                  ->comment('Tiến độ thủ công 0-100, dùng khi không có task con');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn(['start_date', 'manual_progress']);
        });
    }
};
