<?php

use App\Models\Part\PartRelease;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('part_release_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(User::class)->constrained();
            $table->foreignIdFor(PartRelease::class)->nullable()->constrained()->nullOnDelete();
            $table->string('stage');
            $table->unsignedInteger('progress_current')->nullable();
            $table->unsignedInteger('progress_total')->nullable();
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_release_runs');
    }
};
