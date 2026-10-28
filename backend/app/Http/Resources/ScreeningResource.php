<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScreeningResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            // ISO 8601 with time zone, the frontend formats it for display.
            'starts_at' => $this->starts_at->toIso8601String(),
            'ends_at' => $this->ends_at->toIso8601String(),
            'price' => $this->price,
            'language' => $this->language,
            'subtitles' => $this->subtitles,
            'format' => $this->format,
            'hall' => $this->whenLoaded('hall', fn () => [
                'id' => $this->hall->id,
                'name' => $this->hall->name,
                'cinema' => $this->hall->relationLoaded('cinema') ? [
                    'id' => $this->hall->cinema->id,
                    'name' => $this->hall->cinema->name,
                    'city' => $this->hall->cinema->city,
                ] : null,
            ]),
        ];
    }
}
