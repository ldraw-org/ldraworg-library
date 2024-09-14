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
        Schema::create('part_render_views', function (Blueprint $table) {
            $table->id();
            $table->string('part_name');
            $table->string('matrix')->default('1 0 0 0 1 0 0 0 1');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_render_views');
    }
};
