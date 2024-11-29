<?php

namespace Database\Seeders;

use App\LDraw\LibraryConfig;
use App\Models\Part\PartCategory;
use App\Models\Part\PartLicense;
use App\Models\Part\PartType;
use App\Models\Part\PartTypeQualifier;
use App\Settings\LibrarySettings;
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
        $ls = app(LibrarySettings::class);
        if (empty($ls->allowed_header_metas)) {
            $ls->allowed_header_metas = [
                'Name:',
                'Author:',
                '!LDRAW_ORG',
                '!LICENSE',
                '!HELP',
                'BFC',
                '!CATEGORY',
                '!KEYWORDS',
                '!CMDLINE',
                '!HISTORY'
            ];
        }
        if (empty($ls->allowed_body_metas)) {
            $ls->allowed_body_metas = [
                '!TEXMAP',
                '!:',
                'BFC',
                '//',
            ];
        }
        $ls->save();
    }
}
