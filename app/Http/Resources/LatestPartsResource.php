<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\PartEvent
 */
class LatestPartsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'image' => $this->part->isTexmap() ? route('unofficial.download', $this->part->filename) : asset('images/library/unofficial/' . substr($this->part->filename, 0, -4) . '.png'),
            'url' => route('parts.show', $this->part),
            'description' => $this->part->description,
        ];
    }
}
