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
            $table->index('event_type');
            $table->index('vote_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part_events', function (Blueprint $table) {
            $table->dropIndex('part_events_event_type_index');
            $table->dropIndex('part_events_vote_type_index');
        });
    }
};
