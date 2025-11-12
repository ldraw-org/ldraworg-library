<?php

namespace App\Enums\Traits;

trait CanBeOption
{
    public function label(): string
    {
        return preg_replace('/(?<=\p{Ll})(?=\p{Lu})|(?<=\p{L})(?=\p{N})|(?<=\p{N})(?=\p{L})/u', ' ', $this->name);
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
