<?php

use App\Models\Part\Part;
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
        Schema::create('part_messages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(Part::class);
            $table->string('check');
            $table->string('check_type');
            $table->integer('line_number');
            $table->string('type');
            $table->string('value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_messages');
    }
};
