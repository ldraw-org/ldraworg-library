<?php

namespace App\Collections;

use App\Enums\PartCategory;
use Staudenmeir\LaravelAdjacencyList\Eloquent\Graph\Collection;
use App\Enums\PartType;
use App\Models\Part\Part;
use App\Collections\Traits\HasRelease;

/**
 * @extends \Staudenmeir\LaravelAdjacencyList\Eloquent\Graph\Collection<int|string, \App\Models\Part\Part>
 */
class PartCollection extends Collection
{
    use HasRelease;

    public function fixes(): self
    {
        return $this->unofficial()->whereNotNull('official_part');
    }

    public function notFix(): self
    {
        return $this->whereNull('official_part');
    }

    public function partsFolderOnly(): self
    {
        return $this->whereIn('type', [PartType::partsFolderTypes()]);
    }

    public function activeParts(): self
    {
        return $this->whereNotIn('category', [PartCategory::Obsolete, PartCategory::Moved]);
    }

    /**
    * @return array<mixed>
    */
    public function part_release_data(): array
    {
        $data = [
            'total' => $this->count(),
            'new' => $this->whereNull('official_part')->count(),
            'new_of_type' => [],
            'moved' => [],
            'fixed' => [],
            'renamed' => [],
        ];
        foreach (PartType::cases() as $type) {
            if ($type == PartType::Shortcut) {
                continue;
            }
            if ($type->inPartsFolder()) {
                $count = $this->whereNull('official_part')
                    ->partsFolderOnly()
                    ->count();
            } else {
                $count = $this->whereNull('official_part')
                    ->where('type', $type)
                    ->count();
            }
            $data['new_of_type'][$type->value] = $count;
        }
        $this->where('category', PartCategory::Moved)
            ->each(function (Part $part, int $id) use (&$data){
                $data['moved'][] = ['from' => $part->meta_name,  'to' => $part->description];
            });
        $this->whereNotNull('official_part')
            ->where('category', '!=', PartCategory::Moved)
            ->each(function (Part $part, int $id) use (&$data) {
                if ($part->description != $part->official_part->description) {
                    $data['renamed'][] = ['name' => $part->meta_name, 'old' => $part->description, 'new' => $part->official_part->description];
                } else {
                    $data['fixed'][] = ['name' => $part->meta_name, 'description' => $part->description];
                }
            });
        return $data;
    }
}
