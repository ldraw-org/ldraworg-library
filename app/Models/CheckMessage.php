<?php

namespace App\Models;

use App\Casts\CheckItemCast;
use App\Collections\CheckMessageCollection;
use App\Models\Traits\HasPart;
use App\Services\Check\Enums\CheckType;
use Illuminate\Database\Eloquent\Attributes\CollectedBy;
use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[CollectedBy(CheckMessageCollection::class)]
#[Unguarded]
class CheckMessage extends Model
{
    use HasPart, HasFactory;

    /**
     * @return array{
     *     'check': 'App\\Services\\Check\\Contracts\\CheckItem',
     *     'check_type': 'App\\Services\\Check\\Enums\\CheckType',
     *     'admin_override': 'boolean',
     * }
     */
    public function casts(): array
    {
        return [
            'check' => CheckItemCast::class,
            'check_type' => CheckType::class,
            'admin_override' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            $model->check_type = $model->check->type();
        });
    }

    public static function distinctCheckOptions(): array
    {
        return DB::table('check_messages')
            ->select(['check_type', 'check'])
            ->distinct()
            ->get()
            ->mapWithKeys(function ($row) {
                $check = CheckType::from($row->check_type)->enumClass()::from($row->check);
                $key = "{$row->check_type}:{$row->check}";

                return [$key => $check->isMultiLine() ? $check->multiLineHeader() : $check->description()];
            })
            ->all();
    }

    public function message(): string
    {
        return $this->check->message(['line' => $this->line_number, 'value' => $this->value, 'type' => $this->type]);
    }

}
