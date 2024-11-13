<?php

use App\Models\Part\Part;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rebrickable_parts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('part_num');
            $table->string('name');
            $table->string('part_url');
            $table->string('part_img_url')->nullable();
            $table->foreignIdFor(Part::class)->nullable();
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
