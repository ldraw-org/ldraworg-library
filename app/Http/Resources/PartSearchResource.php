<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartSearchResource extends JsonResource
{
   public static string $wrap = 'results';

    public function toArray(Request $request): array
    {
        return [
            'title' => $this->name(),
            'description' => $this->description,
            'url' => $this->isUnofficial() ? route('tracker.show', $this->id) : route('official.show', $this->id),  
        ];
    }
}
