<?php

namespace Database\Factories;

use App\Models\Cinema;
use Illuminate\Database\Eloquent\Factories\Factory;

class HallFactory extends Factory
{
    public function definition(): array
    {
        return [
            'cinema_id' => Cinema::factory(),
            'name' => 'Hall ' . fake()->numberBetween(1, 10),
        ];
    }
}