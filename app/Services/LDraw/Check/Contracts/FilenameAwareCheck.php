<?php

namespace App\Services\LDraw\Check\Contracts;

interface FilenameAwareCheck
{
    public function setFilename(?string $filename): void;
}
