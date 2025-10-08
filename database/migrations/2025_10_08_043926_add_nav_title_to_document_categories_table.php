<?php

use App\Models\Document\Document;
use App\Models\Document\DocumentCategory;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('document_categories', function (Blueprint $table) {
            $table->renameColumn('category', 'title');
            $table->renameIndex('document_categories_category_unique', 'document_categories_title_unique');
            $table->string('slug');
        });
        Schema::table('documents', function (Blueprint $table) {
            $table->renameColumn('nav_title', 'slug');
            $table->unique(['document_category_id', 'slug']);
        });
        
        DocumentCategory::each(function (DocumentCategory $cat) {
            $cat->slug = Str::slug($cat->title);
            $cat->save();
        });
        Document::each(function (Document $doc) {
            $doc->slug = Str::slug($doc->title);
            $doc->save();
        });

        Schema::table('document_categories', function (Blueprint $table) {
            $table->string('slug')->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
