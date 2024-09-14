<?php

use App\Models\Document\DocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('nav_title');
            $table->string('title');
            $table->text('content');
            $table->string('maintainer')->nullable();
            $table->text('revision_history')->nullable();
            $table->boolean('published')->default(false);
            $table->boolean('restricted')->default(false);
            $table->integer('order');
            $table->foreignIdFor(DocumentCategory::class)->constrained();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
