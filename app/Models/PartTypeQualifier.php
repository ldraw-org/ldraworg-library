<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Part;

class PartTypeQualifier extends Model
{
    public $timestamps = false;

    public function parts()
    {
        return $this->hasMany(Part::class);
    }

    public static function findByType($type) {
      return self::firstWhere('type', $type);
    }      
}
