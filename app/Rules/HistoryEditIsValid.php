<?php

namespace App\Rules;

use Illuminate\Translation\PotentiallyTranslatedString;
use App\Models\Part\Part;
use App\Models\Part\PartHistory;
use App\Models\User;
use App\Services\Check\PartChecker;
use App\Services\Check\PartChecks\HistoryIsValid;
use App\Services\Check\PartChecks\HistoryUserIsRegistered;
use App\Services\Parser\ParsedPartCollection;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class HistoryEditIsValid implements ValidationRule, DataAwareRule
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
        $value = collect($value)
            ->map(
                fn (array $state) =>
                '0 !HISTORY ' .
                (new Carbon(Arr::get($state, 'created_at')))->toDateString() .
                ' ' .
                (User::find(Arr::get($state, 'user_id'))?->historyString() ?? '') .
                ' ' .
                Str::of(Arr::get($state, 'comment'))->squish()->trim()->toString()
            );
        $part = Part::find(Arr::get($this->data, 'mountedActions.0.data.id'));
        $p = new ParsedPartCollection($value->implode("\n"));
        $errors = PartChecker::singleCheck($p, new HistoryIsValid());
        if ($errors->isNotEmpty()) {
            $fail($errors->first()->message());
            return;
        }
        $errors = PartChecker::singleCheck($p, new HistoryUserIsRegistered());
        if ($errors->isNotEmpty()) {
            $fail($errors->first()->message());
            return;
        }
        $old_hist = collect($part->history->sortBy('created_at')->map(fn (PartHistory $h) => $h->toString()));
        if ($old_hist->diff($value)->all() && is_null(Arr::get($this->data, 'mountedActions.0.data.editcomment'))) {
            $fail('partcheck.history.alter')->translate();
        }
    }
}
