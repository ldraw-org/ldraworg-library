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
        Schema::table('part_events', function (Blueprint $table) {
            $table->string('deleted_filename')->nullable();
            $table->string('deleted_description')->nullable();
            $table->string('moved_from_filename')->nullable();
            $table->foreignIdFor(\App\Models\Part::class)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part_events', function (Blueprint $table) {
            $table->dropColumn('deleted_filename');
            $table->dropColumn('deleted_description');
            $table->dropColumn('moved_from_filename');
            $table->foreignIdFor(\App\Models\Part::class)->change();
        });
    }
};
