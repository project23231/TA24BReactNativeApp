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

            // Spread screenings over the next two weeks so date filters can be tested.
            'starts_at' => Carbon::instance(fake()->dateTimeBetween('+1 day', '+14 days'))
                ->setTime(
                    fake()->numberBetween(10, 22),
                    fake()->randomElement([0, 15, 30, 45]),
                ),

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
