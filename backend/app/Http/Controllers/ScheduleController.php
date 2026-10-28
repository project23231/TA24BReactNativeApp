<?php

namespace App\Http\Controllers;

use App\Http\Resources\ScreeningResource;
use App\Models\Cinema;
use App\Models\Screening;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class ScheduleController extends Controller
{
    private const TIMEZONE = 'Europe/Tallinn';

    public function cinemas()
    {
        return response()->json([
            'data' => Cinema::query()->orderBy('city')->orderBy('name')->orderBy('id')
                ->get(['id', 'name', 'city', 'address']),
        ]);
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'date' => ['nullable', 'date_format:Y-m-d'],
            'cinema_id' => ['bail', 'nullable', 'integer', 'min:1', 'max:2147483647', 'exists:cinemas,id'],
            'movie_id' => ['bail', 'nullable', 'integer', 'min:1', 'max:2147483647', 'exists:movies,id'],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);
        $date = $filters['date'] ?? now(self::TIMEZONE)->toDateString();
        $from = Carbon::createFromFormat('!Y-m-d', $date, self::TIMEZONE);
        $until = $from->copy()->addDay();

        // A cinema day follows Tallinn time, including 23/25-hour DST days.
        // Already started and cancelled screenings are not available to book.
        if ($from->lt(now())) {
            $from = now();
        }

        $screenings = Screening::query()
            ->with(['movie.genres', 'hall.cinema'])
            ->where('status', 'scheduled')
            ->where('starts_at', '>=', $from->utc())
            ->where('starts_at', '<', $until->utc())
            ->when($filters['movie_id'] ?? null, fn ($query, $id) => $query->where('movie_id', $id))
            ->when($filters['cinema_id'] ?? null, fn ($query, $id) => $query
                ->whereHas('hall', fn ($hall) => $hall->where('cinema_id', $id)))
            ->orderBy('starts_at')->orderBy('id')
            ->paginate($filters['per_page'] ?? 30)->withQueryString();

        return ScreeningResource::collection($screenings);
    }
}
