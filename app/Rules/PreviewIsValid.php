<?php

namespace App\Rules;

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
        $line = $attribute == 'mountedActionsData.0.preview_color' ? $value : Arr::get($this->data['mountedActionsData'][0], 'preview_color', '');
        $line .= ' '. ($attribute == 'mountedActionsData.0.preview_x' ? ' '. $value : Arr::get($this->data['mountedActionsData'][0], 'preview_x', ''));
        $line .= ' '. ($attribute == 'mountedActionsData.0.preview_y' ? $value : Arr::get($this->data['mountedActionsData'][0], 'preview_y', ''));
        $line .= ' '. ($attribute == 'mountedActionsData.0.preview_y' ? $value : Arr::get($this->data['mountedActionsData'][0], 'preview_z', ''));
        $line .= ' '. ($attribute == 'mountedActionsData.0.preview_rotation' ? $value : Arr::get($this->data['mountedActionsData'][0], 'preview_rotation', ''));
        $p = ParsedPart::fromArray(['preview' => $line]);
        $errors = app(\App\LDraw\Check\PartChecker::class)->singleCheck($p, new \App\LDraw\Check\Checks\PreviewIsValid());
        if (count($errors) > 0) {
            $fail($errors[0]);
        }
    }
}
