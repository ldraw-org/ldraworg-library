<?php

namespace App\Rules;

use App\LDraw\Check\PartChecker;
use App\LDraw\Parse\ParsedPart;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;

class PreviewIsValid implements DataAwareRule, ValidationRule
{
    protected $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $line = '16 0 0 0 '. ($attribute == 'mountedActionsData.0.preview_rotation' ? $value : Arr::get($this->data['mountedActionsData'][0], 'preview_rotation', ''));
        $p = ParsedPart::fromArray(['preview' => $line]);
        $errors = (new PartChecker($p))->singleCheck(new \App\LDraw\Check\Checks\PreviewIsValid());
        if (count($errors) > 0) {
            $fail($errors[0]);
        }
    }
}
