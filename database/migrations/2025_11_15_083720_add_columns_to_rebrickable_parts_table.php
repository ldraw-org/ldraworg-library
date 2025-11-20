<?php

use App\Models\LdrawColour;
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
        Schema::table('rebrickable_parts', function (Blueprint $table) {
            $table->boolean('is_local')->default(false);
            $table->integer('rb_part_category_id')->default(11);
            $table->string('ldraw_number')->nullable();
            $table->string('element')->nullable();
            $table->foreignIdFor(LdrawColour::class)->nullable()->constrained()->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rebrickable_parts', function (Blueprint $table) {
            $table->dropColumn('is_local');
            $table->dropColumn('rb_part_category_id');
            $table->dropColumn('ldraw_number');
            $table->dropColumn('element');
            $table->dropForeign('rebrickable_parts_ldraw_colour_id_foreign');
            $table->dropColumn('ldraw_colour_id');
        });
    }
};
