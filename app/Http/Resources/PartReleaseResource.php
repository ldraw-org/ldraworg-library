<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Cache;

class PartReleaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'short' => $this->short,
            'total' => $this->total,
            'new' => $this->new,
            'blurb' => $this->blurb,
            'official_count' => Cache::get('current_official_part_count'),
            'image' => file_exists(public_path('images/updates/' . $this->short . '.png'))
                ? asset('images/updates/' .  $this->short . '.png')
                : asset('images/updates/default.png'),
            'zip_download' => asset('library/updates/lcad' . $this->short . '.zip'),
            'view' => route('part-update.view', $this),
        ];
    }
}
