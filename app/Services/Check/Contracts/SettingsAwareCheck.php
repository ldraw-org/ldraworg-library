<?php

namespace App\Services\Check\Contracts;

use App\Settings\LibrarySettings;

interface SettingsAwareCheck
{
    public function setSettings(LibrarySettings $settings): void;
}
