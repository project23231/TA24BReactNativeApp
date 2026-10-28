<?php

namespace Database\Seeders;

use App\Models\Genre;
use App\Models\Movie;
use Illuminate\Database\Seeder;

class MovieSeeder extends Seeder
{
    /** Runs after GenreSeeder: each movie is linked to its genres by name. */
    public function run(): void
    {
        $movies = require database_path('data/movies.php');
        $genreIds = Genre::pluck('id', 'name');

        foreach ($movies as [$title, $year, $duration, $ageRating, $genres, $description]) {
            $movie = Movie::create([
                'title' => $title,
                'description' => $description,
                'duration_minutes' => $duration,
                'release_date' => "{$year}-01-01",
                'age_rating' => $ageRating,
                'poster_url' => null,
                'trailer_url' => null,
            ]);

            $movie->genres()->attach(
                collect($genres)->map(fn (string $name) => $genreIds[$name]
                    ?? throw new \RuntimeException("Genre '{$name}' of '{$title}' is missing in genres.php"))
            );
        }
    }
}
