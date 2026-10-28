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
            // Build all 100 seats of the hall first and insert them in one query.
            // One create() per seat meant thousands of separate INSERTs.
            $rows = [];

            for ($row = 1; $row <= 10; $row++) {
                for ($number = 1; $number <= 10; $number++) {
                    $rows[] = [
                        'hall_id' => $hall->id,
                        'row_number' => $row,
                        'seat_number' => $number,
                    ];
                }
            }

            Seat::insert($rows);
        }
    }
}
