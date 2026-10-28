<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Ticket;
use Illuminate\Database\Seeder;

class TicketSeeder extends Seeder
{
    public function run(): void
    {
        $bookings = Booking::with('screening.hall')
            ->where('status', 'confirmed')
            ->get();

        foreach ($bookings as $booking) {
            $screening = $booking->screening;

            // 1-4 free seats per booking, so "tickets in a booking" has real data.
            $seats = $screening->hall->seats()
                ->whereDoesntHave('tickets', function ($query) use ($screening) {
                    $query->where('screening_id', $screening->id)
                        ->where('status', 'valid');
                })
                ->inRandomOrder()
                ->limit(fake()->numberBetween(1, 4))
                ->get();

            foreach ($seats as $seat) {
                Ticket::factory()->create([
                    'booking_id' => $booking->id,
                    'screening_id' => $screening->id,
                    'hall_id' => $screening->hall_id,
                    'seat_id' => $seat->id,
                    'price' => $screening->price,
                ]);
            }
        }
    }
}
