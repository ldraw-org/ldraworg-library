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
        Schema::table('sticker_sheets', function (Blueprint $table) {
            $table->json('rebrickable')->nullable()->change();
            $table->json('part_colors')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sticker_sheets', function (Blueprint $table) {
            $table->json('rebrickable')->change();
            $table->json('part_colors')->change();
        });
    }
};
