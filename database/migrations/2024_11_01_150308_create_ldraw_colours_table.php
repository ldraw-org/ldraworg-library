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
        Schema::create('ldraw_colours', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name')->unique();
            $table->integer('code')->unique();
            $table->string('value');
            $table->string('edge');
            $table->integer('alpha')->nullable();
            $table->integer('luminance')->nullable();
            $table->boolean('chrome')->default(false);
            $table->boolean('pearlescent')->default(false);
            $table->boolean('rubber')->default(false);
            $table->boolean('matte_metallic')->default(false);
            $table->boolean('metal')->default(false);
            $table->boolean('glitter')->default(false);
            $table->boolean('speckle')->default(false);
            $table->string('material_value')->nullable();
            $table->integer('material_alpha')->nullable();
            $table->integer('material_luminance')->nullable();
            $table->float('material_fraction')->nullable();
            $table->float('material_vfraction')->nullable();
            $table->float('material_size')->nullable();
            $table->float('material_minsize')->nullable();
            $table->float('material_maxsize')->nullable();
            $table->integer('lego_id')->nullable();
            $table->string('lego_name')->nullable();
            $table->integer('rebrickable_id')->nullable();
            $table->string('rebrickable_name')->nullable();
            $table->integer('brickset_id')->nullable();
            $table->string('brickset_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ldraw_colours');
    }
};
