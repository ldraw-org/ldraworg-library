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
        Schema::drop('part_messages');
        Schema::create('check_messages', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignIdFor(\App\Models\Part\Part::class)->constrained()->onDelete('cascade');
            $table->string('check');
            $table->string('check_type');
            $table->integer('line_number')->nullable();
            $table->string('type')->nullable();
            $table->string('value')->nullable();
            $table->string('text')->nullable();
            $table->boolean('admin_override')->default(false);
        });
        Schema::table('parts', function (Blueprint $table) {
           $table->dropColumn('check_messages');
           $table->foreignIdFor(\App\Models\CheckMessage::class)->nullable()->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('check_messages');
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
        Schema::table('parts', function (Blueprint $table) {
            $table->dropColumn('check_messages');
            $table->json('check_messages')->nullable();
        });
    }
};
