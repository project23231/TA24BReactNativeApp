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
            'row_number' => 1,
            'seat_number' => 1,
        ];
    }
}