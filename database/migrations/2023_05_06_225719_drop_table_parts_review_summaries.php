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
        Schema::dropIfExists('parts_review_summaries');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('parts_review_summaries', function (Blueprint $table) {
            $table->foreignIdFor(Part::class)->constrained();
            $table->foreignIdFor(ReviewSummary::class)->constrained();
            $table->index('part_id');
            $table->index('review_summary_id');
            $table->unique(['part_id', 'review_summary_id']);
        });
    }
};
