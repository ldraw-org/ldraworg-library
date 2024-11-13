<?php

namespace Database\Seeders;

use App\LDraw\LibraryConfig;
use App\Models\Part\PartCategory;
use App\Models\Part\PartEventType;
use App\Models\Part\PartLicense;
use App\Models\Part\PartType;
use App\Models\Part\PartTypeQualifier;
use App\Models\VoteType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        PartType::insert(LibraryConfig::partTypes());
        PartTypeQualifier::insert(LibraryConfig::partTypeQualifiers());
        PartLicense::insert(LibraryConfig::partLicenses());
        PartCategory::insert(LibraryConfig::partCategories());
        PartEventType::insert(LibraryConfig::partEventTypes());
        VoteType::insert(LibraryConfig::voteTypes());
    }
}
