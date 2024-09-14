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
        Schema::create('review_summary_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->integer('order');
            $table->foreignIdFor(\App\Models\ReviewSummary\ReviewSummary::class)->constrained();
            $table->foreignIdFor(\App\Models\Part::class)->nullable()->constrained();
            $table->string('heading')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('review_summary_items');
    }
};
