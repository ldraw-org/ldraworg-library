<?php

use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Enums\Permission;
use App\Livewire\TorsoShortcutHelper;
use App\Models\Part\Part;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $user = User::factory()->create(['ca_confirm' => true]);
    $user->givePermissionTo(Permission::PartSubmitRegular->value);
    $this->actingAs($user);
    $this->parts = torsoShortcutParts();
});

it('fills the name and description from the template and torso', function () {
    Livewire::test(TorsoShortcutHelper::class)
        ->assertSet('data.templateId', $this->parts['template']->id)
        ->set('data.torso', $this->parts['torso']->id)
        ->assertSet('data.name', '76382p01.dat')
        ->assertSet('data.description', 'Minifig Torso Assembly with Logo Pattern');
});

it('does not error when the torso is cleared', function () {
    Livewire::test(TorsoShortcutHelper::class)
        ->set('data.torso', $this->parts['torso']->id)
        ->set('data.torso', null)
        ->assertOk()
        ->assertSet('data.name', '76382p01.dat');
});

it('rebuilds the part fields, name and description and clears part choices when the template changes', function () {
    $partFactory = fn (array $attributes): Part => Part::factory()->official()->createQuietly($attributes + ['type' => PartType::Part]);
    $hand = $partFactory(['filename' => 'parts/3820.dat', 'description' => 'Minifig Hand', 'category' => PartCategory::MinifigHand]);
    $otherTemplate = $partFactory(['filename' => 'parts/16360.dat', 'description' => 'Minifig Torso Assembly Short Arms', 'category' => PartCategory::MinifigUpper]);
    $otherTemplate->body->forceFill(['body' => '1 16 -15 8 0 1 0 0 0 1 0 0 0 1 3820.dat'])->save();
    $otherTemplate->subparts()->attach($hand->id);

    Livewire::test(TorsoShortcutHelper::class)
        ->assertSee('3818 - Minifig Arm Right')
        ->set('data.torso', $this->parts['torso']->id)
        ->set('data.parts.1.variation', '3818p01.dat')
        ->set('data.templateId', $otherTemplate->id)
        ->assertSee('3820 - Minifig Hand')
        ->assertDontSee('3818 - Minifig Arm Right')
        ->assertSet('data.name', '16360p01.dat')
        ->assertSet('data.description', 'Minifig Torso Assembly Short Arms with Logo Pattern')
        ->assertSet('data.parts', []);
});

it('rejects a name without .dat that matches an existing part', function () {
    Part::factory()->unofficial()->createQuietly(['filename' => 'parts/76382p01.dat', 'type' => PartType::Part]);

    Livewire::test(TorsoShortcutHelper::class)
        ->set('data.torso', $this->parts['torso']->id)
        ->set('data.name', '76382p01')
        ->set('data.keywords', 'Set 6399')
        ->set('data.bricklink', '973p01c01')
        ->call('submitFile')
        ->assertHasErrors(['data.name']);
});

it('reports a missing name as a validation error', function () {
    Livewire::test(TorsoShortcutHelper::class)
        ->set('data.torso', $this->parts['torso']->id)
        ->set('data.name', '')
        ->set('data.keywords', 'Set 6399')
        ->set('data.bricklink', '973p01c01')
        ->call('submitFile')
        ->assertHasErrors(['data.name' => 'required']);
});
