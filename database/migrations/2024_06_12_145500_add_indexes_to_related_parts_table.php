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
        Schema::table('related_parts', function (Blueprint $table) {
            $table->dropUnique('related_parts_parent_id_subpart_id_unique');
            $table->primary(['parent_id', 'subpart_id']);
            $table->index(['subpart_id', 'parent_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('related_parts', function (Blueprint $table) {
            $table->unique(['parent_id', 'subpart_id']);
            $table->dropIndex('related_parts_subpart_id_parent_id_index');
        });
    }
};
