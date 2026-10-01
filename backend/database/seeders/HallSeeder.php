<?php

namespace Database\Seeders;

use App\Models\Cinema;
use App\Models\Hall;
use Illuminate\Database\Seeder;

class HallSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Cinema::all() as $cinema) {
            Hall::factory()->for($cinema)->create();
        }
    }
}