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
        Schema::dropIfExists('rebrickable_parts');
        Schema::create('rebrickable_parts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('number')->unique();
            $table->string('name');
            $table->string('url')->nullable();
            $table->string('image_url')->nullable();
            $table->json('bricklink')->nullable();
            $table->json('brickset')->nullable();
            $table->json('brickowl')->nullable();
            $table->json('lego')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rebrickable_parts');
    }
};
