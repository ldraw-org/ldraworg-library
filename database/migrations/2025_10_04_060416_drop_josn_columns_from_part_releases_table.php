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
        Schema::table('part_releases', function (Blueprint $table) {
            $table->dropColumn('moved');
            $table->dropColumn('fixed');
            $table->dropColumn('renamed');
            $table->dropColumn('part_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part_releases', function (Blueprint $table) {
            //
        });
    }
};
