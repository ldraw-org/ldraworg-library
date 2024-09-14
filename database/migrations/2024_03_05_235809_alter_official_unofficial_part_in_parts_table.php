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
        Schema::table('parts', function (Blueprint $table) {
            $table->foreignId('official_part_id')->nullable()->change()->references('id')->on('parts')->constrained();
            $table->foreignId('unofficial_part_id')->nullable()->change()->references('id')->on('parts')->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->bigInteger('official_part_id')->nullable()->change();
            $table->bigInteger('unofficial_part_id')->nullable()->change();
        });
    }
};
