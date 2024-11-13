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
        Schema::create('omr_models', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(\App\Models\User::class)->constrained();
            $table->foreignIdFor(\App\Models\Omr\Set::class)->constrained();
            $table->foreignIdFor(\App\Models\Part\PartLicense::class)->constrained();
            $table->boolean('missing_parts');
            $table->boolean('missing_patterns');
            $table->boolean('missing_stickers');
            $table->boolean('approved');
            $table->boolean('alt_model');
            $table->string('alt_model_name')->nullable();
            $table->json('notes')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('omr_models');
    }
};
