<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Part;
use App\Models\PartEventType;
use App\Models\User;
use App\Models\PartRelease;

class CreatePartEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('part_events', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(PartEventType::class)->constrained();
            $table->boolean('initial_submit')->nullable();
            $table->foreignIdFor(Part::class)->constrained();
            $table->foreignIdFor(User::class)->constrained();
            $table->char('vote_type_code', 1)->nullable();
            $table->foreign('vote_type_code')->references('code')->on('vote_types')->cascadeOnUpdate()->nullOnDelete();
            $table->foreignIdFor(PartRelease::class)->constrained();
            $table->index('user_id');
            $table->index('part_id');
            $table->text('comment')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('part_events');
    }
}
