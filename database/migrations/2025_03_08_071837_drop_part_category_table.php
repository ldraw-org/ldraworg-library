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
            $table->dropForeign('parts_part_category_id_foreign');
            $table->dropColumn('part_category_id');
        });
        Schema::drop('part_categories');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
