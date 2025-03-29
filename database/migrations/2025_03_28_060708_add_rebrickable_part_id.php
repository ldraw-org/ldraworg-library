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
            $table->foreignId('rebrickable_part_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::table('sticker_sheets', function (Blueprint $table) {
            $table->foreignId('rebrickable_part_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rebrickable_part_id');
        });
        Schema::table('sticker_sheets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('rebrickable_part_id');
        });
    }
};
