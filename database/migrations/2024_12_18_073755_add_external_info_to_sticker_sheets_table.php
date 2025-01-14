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
        Schema::table('sticker_sheets', function (Blueprint $table) {
            $table->json('rebrickable');
            $table->foreignIdFor(LdrawColour::class)->nullable()->constrained();
            $table->json('part_colors');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sticker_sheets', function (Blueprint $table) {
            $table->dropColumn('rebrickable');
            $table->dropConstrainedForeignIdFor(LdrawColour::class);
            $table->dropColumn('part_colors');
        });
    }
};
