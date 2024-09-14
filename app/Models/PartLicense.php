<?php

namespace App\Models;

use App\Models\Traits\HasParts;
use Illuminate\Database\Eloquent\Model;

class PartLicense extends Model
{
    use HasParts;

    public $timestamps = false;

    public $fillable = [
        'name',
        'text',
        'in_use'
    ];

    protected function casts(): array
    {
        return [
            'in_use' => 'boolean',
        ];
    }

    public function toString(): string
    {
        return "0 !LICENSE {$this->text}";
    }
}
