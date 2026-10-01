<?php

namespace Database\Seeders;

use App\Models\Hall;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Hall::all() as $hall) {
            for ($row = 1; $row <= 10; $row++) {
                for ($number = 1; $number <= 10; $number++) {
                    Seat::factory()->for($hall)->create([
                        'row_number' => $row,
                        'seat_number' => $number,
                    ]);
                }
            }
        }
    }
}