<?php

namespace Database\Seeders;

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
        $ls = app(LibrarySettings::class);
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
        $ls->allowed_body_metas = [
                '!TEXMAP',
                '!:',
                'BFC',
                '//',
            ];

        $ls->save();
    }
}
