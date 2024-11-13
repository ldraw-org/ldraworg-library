<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Part\Part;

return new class () extends Migration {
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('part_helps', function (Blueprint $table) {
            $table->foreignIdFor(Part::class)->constrained();  //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('part_helps', function (Blueprint $table) {
            $table->dropColumn('part_id');
        });
    }
};
