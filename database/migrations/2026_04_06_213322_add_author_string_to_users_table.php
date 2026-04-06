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
        Schema::table('users', function (Blueprint $table) {
            $table->string('author_string')
                ->storedAs("
                    CASE
                        WHEN is_legacy = 1 THEN realname
                        WHEN is_ptadmin = 1 THEN CONCAT('[', name, ']')
                        ELSE CONCAT(realname, ' [', name, ']')
                    END
                ");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('author_string');
        });
    }
};
