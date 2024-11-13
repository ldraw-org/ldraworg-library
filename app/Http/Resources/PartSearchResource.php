<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \App\Models\Part\Part
 */
class PartSearchResource extends JsonResource
{
    public static $wrap = 'results';

    public function toArray(Request $request): array
    {
        return [
            'title' => $this->name(),
            'description' => $this->description,
            'url' => route('parts.show', $this->id),
        ];
    }
}
