<?php

namespace App\Enums\Traits;

use Illuminate\Support\Str;

trait CanBeOption
{
    public function label(): string
    {
        return $this->customLabel() ?? Str::headline($this->name);
    }

    protected function customLabel(): ?string
    {
        return null;
    }

    public function getLabel(): ?string
    {
        return $this->label();
    }

    /**
    * @param array<\BackedEnum>|array{} $types
    * @return array<int|string, string>
    */
    public static function options(array $types = []): array
    {
        if (count($types) == 0) {
            $types = self::cases();
        }
        $options = [];
        foreach ($types as $type) {
            if (!$type instanceof self) {
                continue;
            }
            $options[$type->value] = $type->label();
        }
        return $options;
    }
}
