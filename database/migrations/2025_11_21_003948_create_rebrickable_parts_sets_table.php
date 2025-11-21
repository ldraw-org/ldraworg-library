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
        Schema::create('rebrickable_parts_sets', function (Blueprint $table) {
            $table->foreignIdFor(\App\Models\RebrickablePart::class);
            $table->foreignIdFor(\App\Models\Omr\Set::class);
            $table->foreignIdFor(\App\Models\LdrawColour::class)->nullable();
            $table->integer('quantity')->nullable();
            $table->index(['rebrickable_part_id', 'set_id']);
            $table->index(['set_id', 'rebrickable_part_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rebrickable_parts_sets');
    }
};
