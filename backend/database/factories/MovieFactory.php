<?php

namespace Database\Factories;

use App\Models\Movie;
use Illuminate\Database\Eloquent\Factories\Factory;

class MovieFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'duration_minutes' => fake()->numberBetween(80, 180),
            'release_date' => fake()->date(),
            'age_rating' => fake()->randomElement(Movie::AGE_RATINGS),
            'poster_url' => null,
            'trailer_url' => null,
        ];
    }
}
