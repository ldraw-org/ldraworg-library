<?php

namespace App\LDraw\Check;

use App\Enums\CheckType;
use App\Enums\PartError;
use ArrayObject;
use Illuminate\Contracts\Support\Arrayable;
use JsonSerializable;

class PartCheckBag implements Arrayable, JsonSerializable
{
    protected array $messages;

    public function __construct(array|ArrayObject $values = [])
    {
        $this->clear();
        if ($values) {
            $this->load($values);
        }
    }

    public function load(array|ArrayObject $values): void
    {
        foreach ($values as $type => $content) {
            if (is_string($type)) {
                $type = CheckType::tryFrom($type);
            } elseif (!$type instanceof CheckType) {
                $type = null;
            }
            if (is_null($type)) {
                continue;
            }
            foreach ($content as $error => $context) {
                $error = PartError::tryFrom($error);
                if (!is_null($error)) {
                    $this->add($error, $context);
                }    
            }
        }
    }

    public function clear(CheckType|array|null $types = null)
    {
        $types = $this->checkTypeArray($types);
        foreach ($types as $type) {
            $this->messages[$type->value] = [];
        }
    }

    public function has(CheckType|array|null $types = null): bool
    {
        $type = $this->checkTypeArray($types);
        foreach ($types as $type) {
            if ($this->messages[$type->value]) {
                return true;
            }
        }
        return false;
    }

    public function doesntHave(CheckType|array|null $types = null): bool
    {
        return !$this->has($types);
    }

    public function hasError(PartError $error): bool 
    {
        foreach (CheckType::cases() as $type) {
            if (array_key_exists($error->value, $this->messages[$type->value])) {
                return true;
            }
        }
        return false;
    }

    public function doesntHaveError(PartError $error): bool 
    {
        return !$this->hasError($error);
    }

    public function add(PartError $error, $context = []) 
    {
        if ($context) {
            $this->messages[$error->type()->value][$error->value][] = $context;
        } elseif (!$this->hasError($error)) {
            $this->messages[$error->type()->value][$error->value] = [];
        }
    }

    public function remove(PartError $error): void 
    {
        unset($this->messages[$error->type()][$error->value]);
    }

    public function get(CheckType|array|null $types = null, bool $translated = false): array
    {
        $types = $this->checkTypeArray($types);

        $m = [];
        foreach ($types as $type) {
            $m = array_merge($m, $this->messages[$type->value]);
        }

        if (!$translated) {
            return $m;
        }

        $errorStrings = [];
        foreach ($m as $error => $context) {
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

    protected function checkTypeArray(CheckType|array|null $types = null): array
    {
        if (is_null($types)) {
            return CheckType::cases();
        } elseif (is_array($types)) {
            return array_filter(array_map(fn (CheckType|string $type) => $type instanceof CheckType ? $type : CheckType::tryFrom($type), $types));
        }
        return [$types];
    }

    public function toArray(): array
    {
        return $this->messages;
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

}
