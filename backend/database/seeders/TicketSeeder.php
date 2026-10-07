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

            $seat = $screening->hall->seats()
                ->whereDoesntHave('tickets', function ($query) use ($screening) {
                    $query->where('screening_id', $screening->id)
                        ->where('status', 'valid');
                })
                ->inRandomOrder()
                ->firstOrFail();

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