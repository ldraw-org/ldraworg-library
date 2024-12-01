<?php

use App\Models\Part\PartLicense;
use App\Models\Part\PartType;
use App\Models\Part\PartTypeQualifier;
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
            $table->dropConstrainedForeignIdFor(PartType::class);
            $table->dropConstrainedForeignIdFor(PartTypeQualifier::class);
            $table->dropConstrainedForeignIdFor(PartLicense::class);
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
