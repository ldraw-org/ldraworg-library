<?php

namespace App\Http\Resources;

use App\Models\Part\PartEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin PartEvent
 */
class PartsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'image' => $this->getFirstMediaUrl('images'),
            'feed-image' => $this->getFirstMediaUrl('images', 'feed-image'),
            'thumb' => $this->getFirstMediaUrl('images', 'thumb'),
            'url' => route('parts.show', $this),
            'description' => $this->description,
            'filename' => $this->filename,
        ];
    }
}
