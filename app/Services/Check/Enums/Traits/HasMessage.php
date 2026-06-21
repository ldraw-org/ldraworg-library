<?php

namespace App\Services\Check\Enums\Traits;

trait HasMessage
{
    public function message(array $replace = []): string
    {
        return strtr($this->description(), array_combine(
            array_map(fn ($key) => ":{$key}", array_keys($replace)),
            $replace,
        ));
    }
}
