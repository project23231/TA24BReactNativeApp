<?php

namespace Database\Factories;

use App\Models\Screening;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BookingFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'screening_id' => Screening::factory(),

            'customer_email' => function (array $attributes) {
                return User::findOrFail($attributes['user_id'])->email;
            },

            'status' => 'confirmed',
        ];
    }
}