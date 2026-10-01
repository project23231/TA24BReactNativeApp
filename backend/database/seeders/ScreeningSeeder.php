<?php

namespace Database\Seeders;

use App\Models\Hall;
use App\Models\Movie;
use App\Models\Screening;
use Illuminate\Database\Seeder;

class ScreeningSeeder extends Seeder
{
    public function run(): void
    {
        $movies = Movie::all();

        foreach (Hall::all() as $hall) {
            Screening::factory()
                ->for($hall)
                ->for($movies->random())
                ->create();
        }
    }
}