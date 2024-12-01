<?php

namespace App\Models\Part;

use App\Models\Traits\HasParts;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperPartCategory
 */
class PartCategory extends Model
{
    use HasParts;

    public $timestamps = false;

    public $fillable = [
        'category',
    ];

    public function toString(): string
    {
        return "0 !CATEGORY {$this->category}";
    }

}
