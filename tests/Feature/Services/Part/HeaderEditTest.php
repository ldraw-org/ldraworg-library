<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Models\User;
use App\Services\Part\HeaderEdit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;

uses(RefreshDatabase::class);

/**
 * The edit form's data for a part, as EditHeaderAction fills it, with the given changes.
 *
 * @param  array<string, mixed>  $changes
 * @return array<string, mixed>
 */
function headerEditData(Part $part, array $changes): array
{
    $data = app(HeaderEdit::class)->setupHeaderData($part, [
        'description' => $part->description,
        'category' => $part->category?->value,
        'type' => $part->type->value,
        'type_qualifier' => $part->type_qualifier?->value,
        'cmdline' => $part->cmdline,
    ]);
    $data['preview_rotation'] = $part->preview;

    return array_merge($data, $changes);
}

it('saves the regenerated header with the edit', function () {
    Queue::fake();
    Auth::login(User::factory()->create());
    $part = Part::factory()->unofficial()->create(['type' => PartType::Part, 'category' => PartCategory::Brick, 'description' => 'Brick  1 x  2']);

    app(HeaderEdit::class)->storeHeaderData($part, headerEditData($part, ['description' => 'Brick  1 x  3', 'help' => "  \n ", 'cmdline' => '  ']));

    expect($part->fresh())
        ->header->toContain('0 Brick  1 x  3')
        ->help->toBeNull()
        ->cmdline->toBeNull();
});
