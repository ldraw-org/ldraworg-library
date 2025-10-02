<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->index('created_at');
        });
        Schema::table('sets', function (Blueprint $table) {
            $table->index('created_at');
        });
        Schema::table('omr_models', function (Blueprint $table) {
            $table->index('created_at');
        });
        Schema::table('part_histories', function (Blueprint $table) {
            $table->index('created_at');
        });
        Schema::table('tracker_histories', function (Blueprint $table) {
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropIndex('parts_created_at_index');
        });
        Schema::table('sets', function (Blueprint $table) {
            $table->dropIndex('sets_created_at_index');
        });
        Schema::table('omr_models', function (Blueprint $table) {
            $table->dropIndex('omr_models_created_at_index');
        });
        Schema::table('part_histories', function (Blueprint $table) {
            $table->dropIndex('part_histories_created_at_index');
        });
        Schema::table('tracker_histories', function (Blueprint $table) {
            $table->dropIndex('tracker_histories_created_at_index');
        });
    }
};
