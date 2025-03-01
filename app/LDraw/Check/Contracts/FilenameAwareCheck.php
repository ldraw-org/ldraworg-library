<?php

namespace App\LDraw\Check\Contracts;

interface FilenameAwareCheck
{
    public function setFilename(?string $filename): void;
}
