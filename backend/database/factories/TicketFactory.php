<?php

namespace Database\Factories;

use App\Models\Booking;
use App\Models\Screening;
use App\Models\Seat;
use Illuminate\Database\Eloquent\Factories\Factory;

class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'booking_id' => Booking::factory(),

            // The ticket must belong to the booking's screening and to a seat
            // in that screening's hall (see the composite foreign keys).
            'screening_id' => fn (array $attributes) => Booking::findOrFail($attributes['booking_id'])->screening_id,

            'hall_id' => fn (array $attributes) => Screening::findOrFail($attributes['screening_id'])->hall_id,

            'seat_id' => fn (array $attributes) => Seat::factory()->create([
                'hall_id' => $attributes['hall_id'],
            ])->id,

            'price' => fn (array $attributes) => Screening::findOrFail($attributes['screening_id'])->price,

            'status' => 'valid',
        ];
    }
}
