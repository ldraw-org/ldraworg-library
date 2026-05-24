<?php

namespace App\Services\Check;

use App\Enums\PartError;
use App\Enums\CheckType;
use Illuminate\Support\Arr;
use Livewire\Wireable;

class CheckMessage implements Wireable
{
    private function __construct(
        public CheckType $check_type,
        public PartError $check,
        public ?int      $line_number = null,
        public ?string   $value = null,
        public ?string   $type = null,
        public ?string   $text = null,
    ) {
    }

    public function toLivewire()
    {
        return $this->toArray();
    }

    public static function fromLivewire($value)
    {
        return self::fromArray($value);
    }

    public static function fromPartError(PartError $error): self
    {
        return new self(CheckType::Error, $error);
    }

    public static function fromArray(array $checkmessage): self
    {
        $checkType = Arr::get($checkmessage, 'check_type');
        if (! $checkType instanceof CheckType) {
            $checkType = CheckType::tryFrom($checkType);
        }
        $error = Arr::get($checkmessage, 'check');
        if (! $error instanceof PartError) {
            $error = PartError::tryFrom($error);
        }
        return new self(
            $checkType,
            $error,
            Arr::get($checkmessage, 'line_number'),
            Arr::get($checkmessage, 'value'),
            Arr::get($checkmessage, 'type'),
            Arr::get($checkmessage, 'text'),
        );
    }

    public function toArray(): array
    {
        return [
            'check_type' => $this->check_type,
            'check' => $this->check,
            'line_number' => $this->line_number,
            'value' => $this->value,
            'type' => $this->type,
            'text' => $this->text,
        ];
    }

    public function message(): string
    {
        return __("partcheck.{$this->check->value}", ['line' => $this->line_number, 'value' => $this->value, 'type' => $this->type]);
    }

}
