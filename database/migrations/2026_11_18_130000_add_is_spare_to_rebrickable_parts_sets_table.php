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
        Schema::table('rebrickable_parts_sets', function (Blueprint $table) {
            $table->boolean('is_spare')->default(false);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rebrickable_parts_sets', function (Blueprint $table) {
            $table->dropColumn('is_spare');
        });
    }
};
