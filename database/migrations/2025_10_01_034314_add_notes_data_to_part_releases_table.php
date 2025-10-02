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
            $table->integer('total')->default(0);
            $table->integer('new')->default(0);
            $table->json('new_of_type');
            $table->json('moved');
            $table->json('fixed');
            $table->json('renamed');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('part_releases', function (Blueprint $table) {
            $table->dropColumn('total');
            $table->dropColumn('new');
            $table->dropColumn('new_of_type');
            $table->dropColumn('moved');
            $table->dropColumn('fixed');
            $table->dropColumn('renamed');
            $table->dropIndex('part_releases_created_at');
        });
    }
};
