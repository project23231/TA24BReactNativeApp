<?php

namespace Database\Factories;

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
            'age_rating' => fake()->randomElement([
                '0+', '6+', '12+', '16+', '18+',
            ]),
            'poster_url' => null,
            'trailer_url' => null,
        ];
    }
}