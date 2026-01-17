<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->longtext('search_text')->nullable();
        });
        DB::statement("
            ALTER TABLE parts 
            ADD FULLTEXT INDEX part_search_fulltext (search_text)
            WITH PARSER ngram
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("
            ALTER TABLE parts 
            DROP INDEX part_search_fulltext
        ");
        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn('search_text');
        });
    }
};
