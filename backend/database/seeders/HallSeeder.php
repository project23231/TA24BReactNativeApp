<?php

namespace Database\Seeders;

use App\Models\Cinema;
use App\Models\Hall;
use Illuminate\Database\Eloquent\Factories\Sequence;
use Illuminate\Database\Seeder;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Cinema::all() as $cinema) {
            // 2-3 halls per cinema, named Hall 1, Hall 2, ... inside each cinema.
            Hall::factory()
                ->count(fake()->numberBetween(2, 3))
                ->for($cinema)
                ->sequence(fn (Sequence $sequence) => ['name' => 'Hall '.($sequence->index + 1)])
                ->create();
        }
    }
}
