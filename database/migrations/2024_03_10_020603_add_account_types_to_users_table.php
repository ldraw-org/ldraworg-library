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
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_synthetic')->default(false);
            $table->boolean('is_legacy')->default(false);
            $table->boolean('is_ptadmin')->default(false);
            $table->dropColumn('account_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_synthetic');
            $table->dropColumn('is_legacy');
            $table->dropColumn('is_ptadmin');
            $table->tinyInteger('account_type')->default(0);
        });
    }
};
