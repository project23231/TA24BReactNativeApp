<?php

namespace Database\Factories;

use App\Models\Hall;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

class ScreeningFactory extends Factory
{
    public function definition(): array
    {
        return [
            'movie_id' => Movie::factory(),
            'hall_id' => Hall::factory(),
            'starts_at' => now()->addWeek()->setTime(18, 0),

            'ends_at' => function (array $attributes) {
                $movie = Movie::findOrFail($attributes['movie_id']);

                return Carbon::parse($attributes['starts_at'])
                    ->addMinutes($movie->duration_minutes);
            },

            'price' => fake()->randomFloat(2, 5, 20),
            'language' => fake()->randomElement(['et', 'en', 'ru']),
            'subtitles' => null,
            'format' => fake()->randomElement(['2D', '3D']),
            'status' => 'scheduled',
        ];
    }
}