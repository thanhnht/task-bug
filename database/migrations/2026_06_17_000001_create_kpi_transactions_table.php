<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kpi_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('task_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('points', 5, 2);           // âm = trừ điểm, dương = thưởng
            $table->string('reason', 500);
            $table->string('period_month', 7);          // YYYY-MM
            $table->timestamps();

            $table->index(['user_id', 'period_month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kpi_transactions');
    }
};
