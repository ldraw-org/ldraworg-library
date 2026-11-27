<?php

use App\Enums\PartStatus;
use App\Models\Part\Part;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('orders parts by status in workflow order rather than alphabetically', function () {
    foreach ([PartStatus::ErrorsFound, PartStatus::Certified, PartStatus::Needs2MoreVotes, PartStatus::AwaitingAdminReview, PartStatus::Needs1MoreVote] as $status) {
        Part::factory()->create(['part_status' => $status]);
    }

    $ascending = Part::query()->orderByStatus()->pluck('part_status')->all();
    $descending = Part::query()->orderByStatus('desc')->pluck('part_status')->all();

    expect($ascending)->toBe([
        PartStatus::Certified,
        PartStatus::AwaitingAdminReview,
        PartStatus::Needs1MoreVote,
        PartStatus::Needs2MoreVotes,
        PartStatus::ErrorsFound,
    ])->and($descending)->toBe(array_reverse($ascending));
});
