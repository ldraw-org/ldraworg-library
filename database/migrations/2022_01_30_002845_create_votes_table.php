<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Part;
use App\Models\User;

class CreateVotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('votes', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Part::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->char('vote_type_code', 1);
            $table->foreign('vote_type_code')->references('code')->on('vote_types')->constrained();
            $table->index('user_id');
            $table->index('part_id');
            $table->unique(['part_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('votes');
    }
}
