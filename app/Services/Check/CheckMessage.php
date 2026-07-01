<?php

namespace App\Services\Check;

use App\Services\Check\Contracts\CheckItem;
use App\Services\Check\Enums\CheckType;
use Illuminate\Support\Arr;
use Livewire\Wireable;

class CheckMessage implements Wireable
{
    public readonly CheckType $check_type;

    private function __construct(
        public CheckItem $check,
        public ?int      $line_number = null,
        public ?string   $value = null,
        public ?string   $type = null,
        public ?string   $text = null,
    ) {
        $this->check_type = $this->check->type();
    }

    public function toLivewire(): array
    {
        return $this->toArray();
    }

    public static function fromLivewire($value): self
    {
        return self::fromArray($value);
    }

    public static function fromPartError(CheckItem $item): self
    {
        return new self($item);
    }

    public static function fromArray(array $checkMessage): self
    {
        return new self(
            Arr::get($checkMessage, 'check'),
            Arr::get($checkMessage, 'line_number'),
            Arr::get($checkMessage, 'value'),
            Arr::get($checkMessage, 'type'),
            Arr::get($checkMessage, 'text'),
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
        return $this->check->message(['line' => $this->line_number, 'value' => $this->value, 'type' => $this->type]);
    }

}
