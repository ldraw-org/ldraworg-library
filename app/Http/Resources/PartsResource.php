<?php

namespace App\Http\Resources;

use App\Models\Part\PartEvent;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

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
            'image' => $this->getFirstMediaUrl('image'),
            'feed_image' => $this->getFirstMediaUrl('image', 'feed-image'),
            'thumb' => $this->getFirstMediaUrl('image', 'thumb'),
            'url' => route('parts.show', $this),
            'description' => $this->description,
            'filename' => $this->filename,
        ];
    }
}
