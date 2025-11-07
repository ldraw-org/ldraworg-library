<?php

namespace App\Rules;

use Illuminate\Translation\PotentiallyTranslatedString;
use App\Enums\PartCategory;
use App\Enums\PartType;
use App\Services\Check\PartChecker;
use App\Services\Check\PartChecks\PatternHasSetKeyword;
use App\Services\Parser\ParsedPartCollection;
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
        if (Arr::get($this->data, $attribute, false)) {
            $name = str_replace(['parts/','p/'], '', Arr::get($this->data, 'mountedActions.0.data.filename', ''));
            $type = PartType::tryFrom(Arr::get($this->data, 'mountedActions.0.data.type', ''))?->ldrawString(true) ?? '';
            $category = PartCategory::tryFrom(Arr::get($this->data, 'mountedActions.0.data.category', ''))?->ldrawString() ?? '';
            $keywords = collect(explode(',', Str::of($value)->trim()->squish()->replace(["/n", ', ',' ,'], ',')->toString()))->filter()->implode(', ');
            $text = "0 Test\n" .
                "0 Name: {$name}\n" .
                "{$type}\n" .
                "{$category}\n" .
                "0 !KEYWORDS {$keywords}";
            $p = new ParsedPartCollection($text);
            $errors = PartChecker::singleCheck($p, new PatternHasSetKeyword());
            if ($errors->isNotEmpty()) {
                $fail($errors->first()->message());
            }
        }

    }
}
