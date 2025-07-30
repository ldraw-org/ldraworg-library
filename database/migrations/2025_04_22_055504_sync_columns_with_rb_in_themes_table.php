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
        Schema::table('themes', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_id')->nullable();
            $table->renameColumn('theme', 'name');
            $table->dropUnique('themes_theme_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('themes', function (Blueprint $table) {
            $table->dropColumn('parent_id');
            $table->renameColumn('name', 'theme');
        });
    }
};
