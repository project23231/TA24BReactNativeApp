<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $genres = Genre::factory()->count(100)->create();

        foreach (Movie::all() as $movie) {
            $selectedGenres = $genres->random(
                fake()->numberBetween(1, 3)
            );

            $movie->genres()->syncWithoutDetaching(
                $selectedGenres->modelKeys()
            );
        }
    }
}