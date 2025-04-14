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
        Schema::table('ldraw_colours', function (Blueprint $table) {
            $table->boolean('fabric')->defualt(0);
            $table->string('material_fabric_type')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ldraw_colours', function (Blueprint $table) {
            $table->dropColumn('fabric');
            $table->dropColumn('material_fabric');
        });
    }
};
