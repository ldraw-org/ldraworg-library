<?php

namespace App\Models\Omr;

use Illuminate\Database\Eloquent\Attributes\Unguarded;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
use Illuminate\Database\Eloquent\Model;

#[Unguarded]
class Theme extends Model
{
    use HasRecursiveRelationships;

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
