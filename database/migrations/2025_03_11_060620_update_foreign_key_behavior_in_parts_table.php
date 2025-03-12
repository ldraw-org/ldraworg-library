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
            $table->dropForeign('parts_base_part_id_foreign');
            $table->foreign('base_part_id')->references('id')->on('parts')->nullOnDelete();

            $table->dropForeign('parts_unofficial_part_id_foreign');
            $table->foreign('unofficial_part_id')->nullable()->references('id')->on('parts')->nullOnDelete();

            $table->dropForeign('parts_sticker_sheet_id_foreign');
            $table->foreign('sticker_sheet_id')->references('id')->on('sticker_sheets')->nullOnDelete();

            $table->dropForeign('parts_unknown_part_number_id_foreign');
            $table->foreign('unknown_part_number_id')->references('id')->on('unknown_part_numbers')->nullOnDelete();

        });

        Schema::table('part_bodies', function (Blueprint $table) {
            $table->dropForeign('part_bodies_part_id_foreign');
            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
        });

        Schema::table('part_events', function (Blueprint $table) {
            $table->dropForeign('part_events_part_id_foreign');
            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
        });

        Schema::table('part_helps', function (Blueprint $table) {
            $table->dropForeign('part_helps_part_id_foreign');
            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
        });

        Schema::table('parts_part_keywords', function (Blueprint $table) {
            $table->dropForeign('parts_part_keywords_part_id_foreign');
            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
            $table->dropForeign('parts_part_keywords_part_keyword_id_foreign');
            $table->foreign('part_keyword_id')->references('id')->on('part_keywords')->cascadeOnDelete();
        });

        Schema::table('part_histories', function (Blueprint $table) {
            $table->dropForeign('part_histories_part_id_foreign');
            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
        });

        Schema::table('user_part_notifications', function (Blueprint $table) {
            $table->dropForeign('user_part_notifications_part_id_foreign');
            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
            $table->dropForeign('user_part_notifications_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('votes', function (Blueprint $table) {
            $table->dropForeign('votes_part_id_foreign');
            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
            $table->dropForeign('votes_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
        Schema::table('unknown_part_numbers', function (Blueprint $table) {
            $table->dropForeign('unknown_part_numbers_user_id_foreign');
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::table('sticker_sheets', function (Blueprint $table) {
            $table->dropForeign('sticker_sheets_ldraw_colour_id_foreign');
            $table->foreign('ldraw_colour_id')->references('id')->on('ldraw_colours')->nullDelete();
        });


        Schema::table('review_summary_items', function (Blueprint $table) {
            $table->dropForeign('review_summary_items_review_summary_id_foreign');
            $table->foreign('review_summary_id')->references('id')->on('review_summaries')->cascadeOnDelete();
            $table->dropForeign('review_summary_items_part_id_foreign');
            $table->foreign('part_id')->references('id')->on('parts')->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            //
        });
    }
};
