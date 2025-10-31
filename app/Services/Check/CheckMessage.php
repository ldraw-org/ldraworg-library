<?php

namespace App\Services\Check;

use App\Enums\PartError;
use Illuminate\Support\Arr;

class CheckMessage 
{
    private function __construct(
        public PartError $error,
        public ?int $lineNumber = null,
        public ?string $value = null,
        public ?string $type = null,
    ) {
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
        );
    }

    public function toArray(): array
    {
        return [
            'error' => $this->error,
            'lineNumber' => $this->lineNumber,
            'value' => $this->value,
            'type' => $this->type,
        ];
    }

    public function message(): string
    {
       return __("newpartcheck.{$this->error->value}", ['line' => $this->lineNumber, 'value' => $this->value, 'type' => $this->type]);
    }
}