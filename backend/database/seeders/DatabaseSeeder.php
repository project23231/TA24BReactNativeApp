<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            CinemaSeeder::class,
            HallSeeder::class,
            SeatSeeder::class,
            MovieSeeder::class,
            GenreSeeder::class,
            UserSeeder::class,
            ScreeningSeeder::class,
            BookingSeeder::class,
            TicketSeeder::class,
        ]);
    }
}