<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Part;
use App\Models\ReviewSummary\ReviewSummary;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parts_review_summaries', function (Blueprint $table) {
            $table->foreignIdFor(Part::class)->constrained();
            $table->foreignIdFor(ReviewSummary::class)->constrained();
            $table->index('part_id');
            $table->index('review_summary_id');
            $table->unique(['part_id', 'review_summary_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parts_review_summaries');
    }
};
