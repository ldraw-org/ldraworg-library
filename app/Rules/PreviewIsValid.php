<?php

namespace App\Rules;

use App\Services\Check\PartChecker;
use App\Services\Check\PartChecks\PreviewIsValid as PartChecksPreviewIsValid;
use App\Services\Parser\ParsedPartCollection;
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
        $line = '16 0 0 0 '. ($attribute == 'mountedActions.0.data.preview_rotation' ? $value : Arr::get($this->data, 'mountedActions.0.data.preview_rotation', ''));
        $p = new ParsedPartCollection($line);
        $errors = app(PartChecker::class)->runSingle(PartChecksPreviewIsValid::class, $p);
        if ($errors->isNotEmpty()) {
            $fail($errors->first()->message());
        }
    }
}
