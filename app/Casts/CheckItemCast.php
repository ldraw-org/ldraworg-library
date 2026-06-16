<?php

namespace App\Casts;

use App\Services\Check\Contracts\CheckItem;
use App\Services\Check\Enums\CheckType;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

class CheckItemCast implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): CheckItem
    {
        $class = CheckType::from($attributes['check_type'])->enumClass();
        return $class::from($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): array
    {
        return [
            'check' => $value->value,
            'check_type' => $value->type()->value
        ];
    }
}
