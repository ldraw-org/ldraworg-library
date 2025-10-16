<?php

namespace App\Enums\Traits;

trait CanBeOption
{
    public function label(): string
    {
        return preg_replace('/(.*?[a-z]{1})([A-Z]{1}.*?)/', '${1} ${2}', $this->name);
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
