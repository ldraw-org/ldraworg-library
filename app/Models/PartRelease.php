<?php

namespace App\Models;

use App\Models\Traits\HasParts;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;

class PartRelease extends Model
{
    use HasParts;
    
    protected $fillable = [
        'name', 
        'short', 
        'notes', 
        'created_at', 
        'part_data'
    ];

    protected $casts = [
        'part_list' => 'array',
        'part_data' => AsArrayObject::class,
    ];
    
    public function notes(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                if (is_null($attributes['part_data'])) {
                    return '';
                }
                $data = json_decode($attributes['part_data'] ?? "{}", true);
                $notes = "Total files: {$data['total_files']}\n" . 
                    "New files: {$data['new_files']}\n";
                foreach ($data['new_types'] as $t) {
                    $notes .= "New {$t['name']}s: {$t['count']}\n";
                }
                return $notes;
            }
        );
    }

    public function toString(): string 
    {
        return $this->short == 'original' ? " ORIGINAL" : " UPDATE {$this->name}";
    }

    public static function current(): self 
    {
        return self::latest()->first();
    }

    public function isLatest(): bool
    {
        return self::current()->id === $this->id;
    }
 }
