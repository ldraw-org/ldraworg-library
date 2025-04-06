<?php

namespace App\LDraw\Check;

use App\Enums\PartError;
use ArrayObject;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Arr;
use JsonSerializable;

class PartCheckBag implements Arrayable, JsonSerializable
{
    protected array $errors = [];

    public function __construct(array|ArrayObject $values = [])
    {
        foreach ($values as $error => $context) {
            $e = PartError::tryFrom($error);
            if (!is_null($e)) {
                $this->add($e, $context);
            }
        }
    }

    public function isEmpty(): bool
    {
        return !$this->errors;
    }

    public function has(PartError $error): bool {
        return Arr::has($this->errors, $error->value);
    }

    public function add(PartError $error, array $context = []): void 
    {
        if ($context) {
            $this->errors[$error->value][] = $context;
        } elseif (!$this->has($error)) {
            $this->errors[$error->value] = [];
        }
    }

    public function remove(PartError $error): void 
    {
        unset($errors, $error->value);
    }

    public function getErrors(): array 
    {
        $errorStrings = [];
        foreach ($this->errors as $error => $context) {
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

    public function toArray(): array
    {
        return $this->errors;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

}
