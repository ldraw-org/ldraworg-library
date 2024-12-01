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
        Schema::drop('part_types');
        Schema::drop('part_type_qualifiers');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
