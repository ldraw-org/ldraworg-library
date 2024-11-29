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
            $table->dropConstrainedForeignId('vote_type_code');
            $table->dropConstrainedForeignId('part_event_type_id');
         });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part_events', function (Blueprint $table) {
            $table->char('vote_type_code', 1)->nullable();
            $table->foreign('vote_type_code')->references('code')->on('vote_types')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignIdFor('part_event_type_id')->references('id')->on('part_events')->constrained();
        });
    }
};
