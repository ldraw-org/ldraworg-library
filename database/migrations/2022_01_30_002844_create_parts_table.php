<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Part\PartCategory;
use App\Models\Part\PartRelease;
use App\Models\Part\PartType;
use App\Models\Part\PartTypeQualifier;
use App\Models\Part\PartLicense;

class CreatePartsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('parts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(PartCategory::class)->nullable()->constrained();
            $table->foreignIdFor(PartRelease::class)->constrained();
            $table->foreignIdFor(PartLicense::class)->constrained();
            $table->string('filename')->index();
            $table->string('description')->index();
            $table->text('header');
            $table->foreignIdFor(PartType::class)->constrained();
            $table->foreignIdFor(PartTypeQualifier::class)->nullable()->constrained();
            $table->bigInteger('official_part_id')->nullable();
            $table->bigInteger('unofficial_part_id')->nullable();
            $table->integer('uncertified_subpart_count')->default(0);
            $table->string('vote_summary')->nullable();
            $table->integer('vote_sort')->default(1);
            $table->index('user_id');
            $table->index('part_category_id');
            $table->unique(['filename','part_release_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('parts');
    }
}
