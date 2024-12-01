<?php

namespace App\Models\Omr;

use App\Models\Traits\HasLicense;
use App\Models\Traits\HasUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @mixin IdeHelperOmrModel
 */
class OmrModel extends Model
{
    use HasUser;
    use HasLicense;

    protected $guarded = [];

    protected $with = [
        'set',
    ];

    protected function casts(): array
    {
        return [
            'notes' => 'array',
            'missing_parts' => 'boolean',
            'missing_patterns' => 'boolean',
            'missing_stickers' => 'boolean',
            'approved' => 'boolean',
        ];
    }


    public function set(): BelongsTo
    {
        return $this->belongsTo(Set::class, 'set_id', 'id');
    }

    public function filename(): string
    {
        $filename = $this->set->number;
        $filename .= $this->alt_model ? '_' . str_replace(' ', '-', $this->alt_model_name) : '';
        return "{$filename}.mpd";
    }
}
