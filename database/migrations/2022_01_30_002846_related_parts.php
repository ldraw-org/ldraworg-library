<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RelatedParts extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('related_parts', function (Blueprint $table) {
            $table->foreignId('parent_id')->references('id')->on('parts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('subpart_id')->references('id')->on('parts')->cascadeOnDelete()->cascadeOnUpdate();
            $table->index('parent_id');
            $table->index('subpart_id');
            $table->unique(['parent_id', 'subpart_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('related_parts');
    }
}
