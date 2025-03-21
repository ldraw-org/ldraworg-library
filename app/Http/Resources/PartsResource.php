<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Part\PartEvent
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
            'image' => $this->isTexmap() ? route("{$this->libFolder()}.download", $this->filename) : version("images/library/{$this->imagePath()}"),
            'url' => route('parts.show', $this),
            'description' => $this->description,
            'filename' => $this->filename,
        ];
    }
}
