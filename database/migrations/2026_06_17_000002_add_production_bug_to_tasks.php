<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('is_production_bug')->default(false)->after('type');
            $table->foreignId('linked_story_id')
                  ->nullable()
                  ->constrained('tasks')
                  ->nullOnDelete()
                  ->after('is_production_bug');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['linked_story_id']);
            $table->dropColumn(['is_production_bug', 'linked_story_id']);
        });
    }
};
