<?php

namespace Tests\Feature;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MovieListingsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_empty_catalogue_returns_paginated_json(): void
    {
        $this->getJson('/api/movies')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_listing_reads_movies_and_genres_with_stable_pagination(): void
    {
        $genre = Genre::create(['name' => 'Comedy']);
        for ($i = 0; $i < 13; $i++) {
            $movie = Movie::create([
                'title' => sprintf('Film %02d', $i), 'description' => 'Cinema test',
                'duration_minutes' => 100, 'age_rating' => '12+', 'release_date' => '2026-10-01',
            ]);
            $movie->genres()->attach($genre);
        }
        $this->getJson('/api/movies?per_page=12')->assertOk()
            ->assertJsonCount(12, 'data')->assertJsonPath('meta.total', 13)
            ->assertJsonPath('data.0.title', 'Film 00')
            ->assertJsonPath('data.0.genres.0.name', 'Comedy');
        $this->getJson('/api/movies?per_page=12&page=2')->assertOk()
            ->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Film 12');
        $this->getJson('/api/movies?page=0')->assertUnprocessable();
    }
}
