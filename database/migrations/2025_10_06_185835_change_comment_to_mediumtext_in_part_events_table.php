<?php

use App\Models\Part\PartEvent;
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
        Schema::table('part_events', function (Blueprint $table) {
            $table->renameColumn('comment', 'old_comment');
            $table->mediumText('comment')->nullable();
        });
        foreach(PartEvent::all() as $e) {
            $e->comment = $e->old_comment;
            $e->save();
        }
        Schema::table('part_events', function (Blueprint $table) {
            $table->dropColumn('old_comment');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
