<?php

namespace App\LDraw\Check;

use App\Enums\PartError;
use Illuminate\Support\Arr;

class ErrorCheckBag
{
    protected array $errors = [];

    public function isEmpty(): bool
    {
        return !$this->errors;
    }

    public function has(PartError $error): bool {
        return Arr::has($this->errors, $error->value);
    }

    public function add(PartError $error, array $context = []): void {
        if ($context) {
            $this->errors[$error->value][] = $context;
        } elseif (!$this->has($error)) {
            $this->errors[$error->value] = [];
        }
    }

    public function getErrors(): array {
        return self::errorsFromArray($this->errors);
    }

    public function toArray(): array
    {
        return $this->errors;
    }

    public static function errorsFromArray(array $errorArray): array
    {
        $errorStrings = [];
        foreach ($errorArray as $error => $context) {
            if (!$context) {
                $errorStrings[] = __("partcheck.{$error}");
                continue;
            }
            foreach ($context as $replace) {
                $errorStrings[] = __("partcheck.{$error}", $replace);
            }
        }
        return $errorStrings;
    }
}
