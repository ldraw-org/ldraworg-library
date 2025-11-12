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
            $table->index(['id', 'part_status'], 'parts_id_status_idx');
            $table->index(['id', 'part_release_id'], 'parts_id_release_idx');
            $table->index(
                ['id', 'part_status', 'part_release_id', 'ready_for_admin'],
                'parts_id_status_covering_idx'
            );
            $table->index(['id', 'base_part_id'], 'parts_id_base_idx');
            $table->index(['part_status', 'type'], 'parts_status_type_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropIndex('parts_id_status_idx');
            $table->dropIndex('parts_id_release_idx');
            $table->dropIndex('parts_id_status_covering_idx');
            $table->dropIndex('parts_id_base_idx');
            $table->dropIndex('parts_status_type_idx');
        });
    }
};
