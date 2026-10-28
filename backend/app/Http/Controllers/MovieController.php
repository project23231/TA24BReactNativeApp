<?php

namespace App\Http\Controllers;

use App\Http\Requests\MovieIndexRequest;
use App\Http\Resources\MovieResource;
use App\Models\Movie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Carbon;

class MovieController extends Controller
{
    /** A selected date means a calendar day in Tallinn, see "Time zone" in the README. */
    private const TIMEZONE = 'Europe/Tallinn';

    /**
     * GET /api/movies
     * Movie list with search, filters, sorting and pagination.
     */
    public function index(MovieIndexRequest $request): AnonymousResourceCollection
    {
        $filters = $request->validated();

        $sort = $filters['sort'] ?? 'title';
        $direction = $filters['direction'] ?? 'asc';

        $movies = Movie::query()
            ->with('genres')

            // Part of the title, case-insensitive (ILIKE in PostgreSQL).
            ->when($filters['search'] ?? null, function (Builder $query, string $search) {
                $query->whereLike('title', '%'.$this->escapeLike($search).'%');
            })

            // Movies that have at least one of the selected genres.
            ->when($filters['genre_ids'] ?? null, function (Builder $query, array $genreIds) {
                $query->whereHas('genres', fn (Builder $genres) => $genres->whereIn('genres.id', $genreIds));
            })

            ->when($filters['age_ratings'] ?? null, function (Builder $query, array $ratings) {
                $query->whereIn('age_rating', $ratings);
            })

            // Movies that have a scheduled screening on that day which has not started yet.
            ->when($filters['date'] ?? null, function (Builder $query, string $date) {
                [$from, $to] = $this->dayRange($date);

                $query->whereHas('screenings', fn (Builder $screenings) => $screenings
                    ->where('status', 'scheduled')
                    ->where('starts_at', '>=', $from)
                    ->where('starts_at', '<', $to));
            })

            // $direction is validated to be asc or desc, so it is safe in raw SQL.
            ->when(
                $sort === 'release_date',
                fn (Builder $query) => $query->orderByRaw("release_date {$direction} nulls last"),
                fn (Builder $query) => $query->orderBy('title', $direction),
            )
            ->orderBy('id')

            ->paginate($filters['per_page'] ?? 20)
            ->withQueryString();

        return MovieResource::collection($movies);
    }

    /**
     * GET /api/movies/{id}
     * One movie with its genres and upcoming screenings. Unknown id returns 404.
     */
    public function show(Movie $movie): MovieResource
    {
        $movie->load([
            'genres',
            'screenings' => fn ($query) => $query
                ->where('status', 'scheduled')
                ->where('starts_at', '>=', now())
                ->orderBy('starts_at')
                ->with('hall.cinema'),
        ]);

        return new MovieResource($movie);
    }

    /**
     * Start and end of a calendar day in Tallinn, converted to UTC for the query.
     * Today starts from the current time, so past screenings are not counted.
     *
     * @return array{0: Carbon, 1: Carbon}
     */
    private function dayRange(string $date): array
    {
        $from = Carbon::parse($date, self::TIMEZONE)->startOfDay();
        $to = $from->copy()->addDay();

        if ($from->lt(now())) {
            $from = now();
        }

        return [$from->utc(), $to->utc()];
    }

    /** Treat % and _ typed by the user as normal characters, not LIKE wildcards. */
    private function escapeLike(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
