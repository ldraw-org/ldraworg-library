<?php

namespace App\Rules;

use App\Models\Part\Part;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;

class PatternKeyword implements DataAwareRule, ValidationRule
{
    /**
     * Indicates whether the rule should be implicit.
     *
     * @var bool
     */
    public $implicit = true;

    /**
     * All of the data under validation.
     *
     * @var array<string, mixed>
     */
    protected $data = [];

    // ...

    /**
     * Set the data under validation.
     *
     * @param  array<string, mixed>  $data
     */
    public function setData(array $data): static
    {
        $this->data = $data;

        return $this;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $keywords = array_map(fn(string $kw) => trim($kw), explode(',', str_replace("\n", ",", $value)));
        $part = Part::find($this->data['part']['id']);
        if (
            $part->type->inPartsFolder() &&
            $part->category->category !== 'Moved' &&
            $part->category->category !== 'Sticker' &&
            $part->category->category !== 'Sticker Shortcut' &&
            ! app(\App\LDraw\Check\PartChecker::class)->checkPatternForSetKeyword($part->name(), $keywords)
        ) {
            $fail('partcheck.keywords')->translate();
        }
    }
}

