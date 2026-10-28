<?php

namespace App\Http\Controllers;

use App\Http\Resources\GenreResource;
use App\Models\Genre;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class GenreController extends Controller
{
    /**
     * GET /api/genres
     * Genres for the genre filter. Only genres that have movies,
     * so the filter never offers an option with no results.
     */
    public function index(): AnonymousResourceCollection
    {
        $genres = Genre::query()
            ->whereHas('movies')
            ->orderBy('name')
            ->get();

        return GenreResource::collection($genres);
    }
}
