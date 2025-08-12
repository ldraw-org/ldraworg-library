<?php

namespace App\Models\Omr;

use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
use Illuminate\Database\Eloquent\Model;

/**
 * @mixin IdeHelperTheme
 */
class Theme extends Model
{
    use HasRecursiveRelationships;

    protected $guarded = [];

    public function getCustomPaths()
    {
        return [
            [
                'name' => 'theme_path',
                'column' => 'name',
                'separator' => ' > ',
                'reverse' => true,
            ],
        ];
    }

    public function displayString(): string
    {
        $t = $this->ancestorsAndSelf()->depthFirst()->get();

        return $t[$t->count() - 1]->theme_path;
    }
}
