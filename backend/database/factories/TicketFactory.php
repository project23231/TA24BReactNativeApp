<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'price' => fake()->randomFloat(2, 5, 20),
            'status' => 'valid',
        ];
    }
}