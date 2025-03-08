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
        Schema::table('part_events', function (Blueprint $table) {
            $table->dropIndex('part_events_part_release_id_index');
        });
        Schema::table('parts', function (Blueprint $table) {
            $table->dropForeign('parts_official_part_id_foreign');
            $table->dropColumn('official_part_id');
            $table->dropIndex('parts_unofficial_part_id_index');
            $table->dropIndex('parts_part_release_id_index');
            $table->dropColumn('uncertified_subpart_count');
            $table->dropColumn('vote_summary');

        });
        Schema::table('sticker_sheets', function (Blueprint $table) {
            $table->dropColumn('rebrickable_part_id');
        });
        Schema::dropIfExists('rebrickable_parts');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('settings');
            $table->dropColumn('profile_settings');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
