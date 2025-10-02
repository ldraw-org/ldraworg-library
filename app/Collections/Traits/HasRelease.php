<?php

namespace App\Collections\Traits;

trait HasRelease
{
    public function official(): self
    {
        return $this->whereNotNull('part_release_id');
    }

    public function unofficial(): self
    {
        return $this->whereNull('part_release_id');
    }

}