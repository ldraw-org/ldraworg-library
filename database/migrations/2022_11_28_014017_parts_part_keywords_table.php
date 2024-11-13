<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Part\Part;
use App\Models\Part\PartKeyword;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parts_part_keywords', function (Blueprint $table) {
            $table->foreignIdFor(Part::class)->constrained();
            $table->foreignIdFor(PartKeyword::class)->constrained();
            $table->index('part_id');
            $table->index('part_keyword_id');
            $table->unique(['part_id', 'part_keyword_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parts_part_keywords');
    }
};
