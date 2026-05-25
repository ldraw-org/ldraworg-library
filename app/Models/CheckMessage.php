<?php

namespace App\Models;

use App\Collections\CheckMessageCollection;
use App\Enums\CheckType;
use App\Enums\PartError;
use App\Models\Part\Part;
use App\Models\Traits\HasPart;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[CollectedBy(CheckMessageCollection::class)]
#[Unguarded]
class CheckMessage extends Model
{
    use HasPart;

    /**
     * @return array{
     *     'check': 'App\\Enums\\PartError',
     *     'check_type': 'App\\Enums\\CheckType',
     *     'admin_override': 'boolean',
     * }
     */
    public function casts(): array
    {
        return [
            'check' => PartError::class,
            'check_type' => CheckType::class,
            'admin_override' => 'boolean',
        ];
    }

    public function message(): string
    {
        return __("partcheck.{$this->check->value}", ['line' => $this->line_number, 'value' => $this->value, 'type' => $this->type]);
    }

}
