<?php

namespace App\Services\Check;

use App\Enums\PartError;
use Illuminate\Support\Arr;
use Livewire\Wireable;

class CheckMessage implements Wireable
{
    private function __construct(
        public PartError $error,
        public ?int $lineNumber = null,
        public ?string $value = null,
        public ?string $type = null,
        public ?string $text = null,
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
        return new self($error);
    }

    public static function fromArray(array $checkmessage): self
    {
        $error = Arr::get($checkmessage, 'error');
        if (! $error instanceof PartError) {
            $error = PartError::tryFrom($error);
        }
        return new self(
            $error,
            Arr::get($checkmessage, 'lineNumber'),
            Arr::get($checkmessage, 'value'),
            Arr::get($checkmessage, 'type'),
            Arr::get($checkmessage, 'text'),           
        );
    }

    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'lineNumber' => $this->lineNumber,
            'value' => $this->value,
            'type' => $this->type,
            'text' => $this->text,
        ];
    }

    public function message(): string
    {
       return __("partcheck.{$this->error->value}", ['line' => $this->lineNumber, 'value' => $this->value, 'type' => $this->type]);
    }
}