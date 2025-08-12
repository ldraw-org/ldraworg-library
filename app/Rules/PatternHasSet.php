<?php

namespace App\Rules;

use App\LDraw\Check\Checks\PatternHasSetKeyword;
use Illuminate\Translation\PotentiallyTranslatedString;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\LDraw\Check\PartChecker;
use App\LDraw\Parse\ParsedPart;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class PatternHasSet implements DataAwareRule, ValidationRule
{
    /**
     * Indicates whether the rule should be implicit.
     *
     * @var bool
     */
    public $implicit = true;

    protected $data = [];

    public function setData(array $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param Closure(string, ?string=):PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Arr::get($this->data, 'mountedActionsData.0.is_pattern', false)) {
            $p = ParsedPart::fromArray([
                'name' => str_replace(['parts/','p/'], '', Arr::get($this->data, 'mountedActionsData.0.filename', '')),
                'type' => PartType::tryFrom(Arr::get($this->data, 'mountedActionsData.0.type', '')),
                'metaCategory' => PartCategory::tryFrom(Arr::get($this->data, 'mountedActionsData.0.category', '')),
                'keywords' => collect(explode(',', Str::of($value)->trim()->squish()->replace(["/n", ', ',' ,'], ',')->toString()))->filter()->all()
            ]);
            $errors = (new PartChecker($p))->singleCheck(new PatternHasSetKeyword());
            if ($errors) {
                $fail($errors[0]);
            }
        }

    }
}
