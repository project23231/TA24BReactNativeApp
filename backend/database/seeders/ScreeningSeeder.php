<?php

namespace Database\Seeders;

use App\Models\Hall;
use App\Models\Movie;
use App\Models\Screening;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ScreeningSeeder extends Seeder
{
    public function run(): void
    {
        $movies = Movie::all();

        foreach (Hall::all() as $hall) {
            // Each hall gets several screenings on one day, one after another
            // with a 20-minute break, so screenings in a hall never overlap.
            $startsAt = Carbon::today()
                ->addDays(fake()->numberBetween(1, 14))
                ->setTime(
                    fake()->numberBetween(10, 13),
                    fake()->randomElement([0, 15, 30, 45]),
                );

            $count = fake()->numberBetween(2, 4);

            for ($i = 0; $i < $count; $i++) {
                $screening = Screening::factory()
                    ->for($hall)
                    ->for($movies->random())
                    ->create(['starts_at' => $startsAt]);

                $startsAt = $screening->ends_at->copy()->addMinutes(20);
            }
        }
    }
}
