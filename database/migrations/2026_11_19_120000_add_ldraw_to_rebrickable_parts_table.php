<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table('rebrickable_parts', function (Blueprint $table) {
            $table->json('ldraw')->nullable()->after('lego');
            // Multi-valued index so linking a part by its LDraw number (json_contains) avoids a full table scan
            $table->rawIndex('(CAST(`ldraw` AS CHAR(64) ARRAY))', 'rebrickable_parts_ldraw_index');
        });
    }

    public function down(): void
    {
        Schema::table('rebrickable_parts', function (Blueprint $table) {
            $table->dropIndex('rebrickable_parts_ldraw_index');
            $table->dropColumn('ldraw');
        });
    }
};
