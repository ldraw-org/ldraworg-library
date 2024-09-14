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
        Schema::table('vote_types', function (Blueprint $table) {
            $table->renameColumn('sort', 'order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vote_types', function (Blueprint $table) {
            $table->renameColumn('order', 'sort');
        });
    }
};
