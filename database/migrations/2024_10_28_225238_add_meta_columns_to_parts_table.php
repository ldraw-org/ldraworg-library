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
            $table->boolean('is_pattern')->default(false);
            $table->boolean('is_composite')->default(false);
            $table->boolean('is_dual_mould')->default(false);
            $table->foreignId('base_part_id')->nullable()->references('id')->on('parts')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn('is_pattern');
            $table->dropColumn('is_composite');
            $table->dropColumn('is_dual_mould');
            $table->dropColumn('base_part_id');
        });
    }
};
