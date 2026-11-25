<?php

namespace App\Services\Part\Release;

use App\Models\Part\PartRelease;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Writes the Inno Setup script for a release's Windows installer from the committed template, for the installer
 * to be built from by hand. The script holds the AppId, so it is written outside the publicly served storage.
 */
class InstallerScript
{
    public const string PATH = 'installer/LDrawParts.iss';

    /**
     * @return bool false when the template or AppId is not set up, which only skips the script
     */
    public function write(PartRelease $release): bool
    {
        $template = config('ldraw.installer_template');
        $appId = config('ldraw.installer_app_id');

        if (blank($appId) || !is_file($template)) {
            Log::warning('Installer script not written: set LDRAW_INSTALLER_APP_ID and commit the template', [
                'template' => $template,
            ]);

            return false;
        }

        Storage::put(self::PATH, strtr(file_get_contents($template), [
            '@RELEASE_NAME@' => $release->name,
            '@RELEASE_SHORT@' => $release->short,
            '@APP_ID@' => $appId,
        ]));

        return true;
    }
}
