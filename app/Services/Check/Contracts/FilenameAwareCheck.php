<?php

namespace App\Services\Check\Contracts;

interface FilenameAwareCheck
{
    public function setFilename(?string $filename): void;
}
