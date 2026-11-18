<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class () extends Migration {
    /**
     * Existing set parts predate the colour, spare and print_of fixes, so each set must sync once more.
     */
    public function up(): void
    {
        DB::table('sets')->update(['refreshed_at' => null]);
    }

    /**
     * The previous sync times are not recoverable.
     */
    public function down(): void
    {
    }
};
