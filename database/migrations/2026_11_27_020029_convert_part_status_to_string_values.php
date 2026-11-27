<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Legacy integer part status => string value.
     *
     * @var array<int, string>
     */
    private const array STATUSES = [
        0 => 'official',
        1 => 'certified',
        2 => 'awaiting_admin_review',
        3 => 'needs_1_more_vote',
        4 => 'needs_2_more_votes',
        5 => 'uncertified_subfiles',
        6 => 'errors_found',
    ];

    /**
     * Snapshots taken before the November 2025 status renumbering contain key 5 and used
     * 3 = Needs More Votes (not yet split), 4 = Uncertified Subfiles and 5 = Errors Found.
     * The renumbering copied 5 to 6 and 4 to 5 without removing the originals.
     *
     * @var array<int, string>
     */
    private const array PRE_RENUMBERING_HISTORY_KEYS = [
        1 => 'certified',
        2 => 'awaiting_admin_review',
        3 => 'needs_more_votes',
        4 => 'uncertified_subfiles',
        6 => 'errors_found',
    ];

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('parts', function (Blueprint $table) {
            $table->string('part_status', 32)->default('certified')->change();
        });
        DB::table('parts')->update(['part_status' => $this->caseExpression(self::STATUSES)]);

        $this->rewriteHistory(function (array $data): array {
            $keys = array_key_exists(5, $data) ? self::PRE_RENUMBERING_HISTORY_KEYS : self::STATUSES;
            $converted = [];
            foreach ($keys as $old => $new) {
                if (array_key_exists($old, $data)) {
                    $converted[$new] = (int) $data[$old];
                }
            }
            return $converted;
        });
    }

    /**
     * Reverse the migrations. The duplicated legacy history keys are not restored.
     */
    public function down(): void
    {
        DB::table('parts')->update(['part_status' => $this->caseExpression(array_flip(self::STATUSES))]);
        Schema::table('parts', function (Blueprint $table) {
            $table->integer('part_status')->default(1)->change();
        });

        $this->rewriteHistory(function (array $data): array {
            $statuses = array_flip(self::STATUSES) + ['needs_more_votes' => 3];
            $converted = [];
            foreach ($data as $key => $count) {
                $converted[$statuses[$key]] = ($converted[$statuses[$key]] ?? 0) + $count;
            }
            return $converted;
        });
    }

    /**
     * @param array<int|string, int|string> $map
     */
    private function caseExpression(array $map): \Illuminate\Contracts\Database\Query\Expression
    {
        $sql = 'CASE part_status';
        foreach ($map as $from => $to) {
            $sql .= ' WHEN ' . DB::getPdo()->quote((string) $from) . ' THEN ' . DB::getPdo()->quote((string) $to);
        }

        return DB::raw($sql . ' ELSE part_status END');
    }

    /**
     * @param \Closure(array<int|string, int|string>): array<int|string, int> $convert
     */
    private function rewriteHistory(\Closure $convert): void
    {
        DB::table('tracker_histories')->orderBy('id')->chunkById(500, function ($rows) use ($convert) {
            foreach ($rows as $row) {
                DB::table('tracker_histories')
                    ->where('id', $row->id)
                    ->update(['history_data' => json_encode($convert(json_decode($row->history_data, true)))]);
            }
        });
    }
};
