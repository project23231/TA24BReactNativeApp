<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MovieResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'duration_minutes' => $this->duration_minutes,
            'release_date' => $this->release_date,
            'age_rating' => $this->age_rating,
            'poster_url' => $this->poster_url,
            'trailer_url' => $this->trailer_url,
            'genres' => GenreResource::collection($this->whenLoaded('genres')),
            // Only on the movie details page (GET /api/movies/{id}).
            'screenings' => ScreeningResource::collection($this->whenLoaded('screenings')),
        ];
    }
}
