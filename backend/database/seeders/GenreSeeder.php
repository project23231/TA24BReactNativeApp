<?php

namespace Database\Seeders;

use App\Models\Genre;
use Illuminate\Database\Seeder;

class GenreSeeder extends Seeder
{
    public function run(): void
    {
        $names = require database_path('data/genres.php');

        Genre::insert(array_map(fn (string $name) => ['name' => $name], $names));
    }
}
