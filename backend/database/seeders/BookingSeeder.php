<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Screening;
use App\Models\User;
use Illuminate\Database\Seeder;

class BookingSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        foreach (Screening::all() as $screening) {
            Booking::factory()
                ->for($screening)
                ->for($users->random())
                ->create();
        }
    }
}