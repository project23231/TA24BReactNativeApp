<?php

use App\Http\Controllers\GenreController;
use App\Http\Controllers\MovieController;
use App\Http\Controllers\ScheduleController;
use Illuminate\Support\Facades\Route;

// Every route in this file gets the /api prefix (see bootstrap/app.php).

Route::get('/movies', [MovieController::class, 'index']);
// Only digits, at most 9 so the id fits in a PostgreSQL integer.
// Anything else (/api/movies/abc) is 404 instead of a database error.
Route::get('/movies/{movie}', [MovieController::class, 'show'])
    ->where('movie', '[0-9]{1,9}');

Route::get('/genres', [GenreController::class, 'index']);

Route::get('/ping', fn () => response()->json(['message' => 'API works']));

Route::get('/cinemas', [ScheduleController::class, 'cinemas']);
Route::get('/screenings', [ScheduleController::class, 'index']);
