<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_part_notifications', function (Blueprint $table) {
            $table->dropUnique('user_part_notifications_user_id_part_id_unique');
            $table->primary(['part_id', 'user_id']);
            $table->index(['user_id', 'part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_part_notifications', function (Blueprint $table) {
            $table->unique(['user_id', 'part_id']);
            $table->dropIndex('user_part_notifications_user_id_part_id_index');
        });
    }
};
