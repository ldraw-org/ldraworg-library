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
        Schema::table('review_summaries', function (Blueprint $table) {
            $table->dropColumn('page_id');
            $table->dropColumn('group_header');
            $table->integer('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('review_summaries', function (Blueprint $table) {
            $table->integer('page_id');
            $table->string('group_header');
            $table->dropColumn('order');
        });
    }
};
