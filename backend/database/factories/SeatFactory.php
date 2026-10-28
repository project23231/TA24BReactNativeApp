<?php

namespace Database\Factories;

use App\Models\Hall;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'hall_id' => Hall::factory(),
            'row_number' => fake()->numberBetween(1, 20),
            // unique() keeps (hall_id, row_number, seat_number) unique
            // when several seats are created for the same hall.
            'seat_number' => fake()->unique()->numberBetween(1, 10000),
        ];
    }
}
