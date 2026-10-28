# API

Base URL when Laravel runs locally: `http://127.0.0.1:8000/api`

All responses are JSON. Times are ISO 8601 in UTC
(`2026-10-30T16:00:00+00:00`), the frontend formats them for display.

| Code | Meaning |
|---|---|
| 200 | OK |
| 404 | Not found (unknown or non-numeric movie id) |
| 422 | Invalid query parameter, `errors` lists the problem for each field |

## GET /api/movies

Movie list with search, filters, sorting and pagination.

| Parameter | Example | Meaning |
|---|---|---|
| `search` | `ring` | Part of the title, case-insensitive. Max 100 characters. |
| `genre_ids[]` | `genre_ids[]=5&genre_ids[]=47` | Movies with **at least one** of these genres. Ids from `GET /api/genres`. |
| `age_ratings[]` | `age_ratings[]=12%2B` | One or more of `0+`, `6+`, `12+`, `16+`, `18+`. |
| `date` | `2026-10-30` | Movies with a scheduled screening on that day (Europe/Tallinn). For today only screenings that have not started yet count. |
| `sort` | `release_date` | `title` (default) or `release_date`. |
| `direction` | `desc` | `asc` (default) or `desc`. Movies without a release date are last. |
| `per_page` | `20` | 1–50, default 20. |
| `page` | `2` | Page number, default 1. |

All filters can be combined. `+` in a URL means a space, so write
`12+` as `12%2B`. The API also accepts `12+`, `12 ` and `12`.

Example: `GET /api/movies?search=the&genre_ids[]=28&date=2026-10-30&sort=release_date&direction=desc`

```json
{
  "data": [
    {
      "id": 94,
      "title": "The Social Network",
      "description": "A Harvard student creates Facebook and ends up in lawsuits with his closest friends.",
      "duration_minutes": 120,
      "release_date": "2010-01-01",
      "age_rating": "12+",
      "poster_url": null,
      "trailer_url": null,
      "genres": [
        { "id": 9, "name": "Biography" },
        { "id": 28, "name": "Drama" }
      ]
    }
  ],
  "links": { "first": "...", "last": "...", "prev": null, "next": null },
  "meta": { "current_page": 1, "last_page": 1, "per_page": 20, "total": 1 }
}
```

Validation error example: `GET /api/movies?age_ratings[]=3%2B` → 422

```json
{
  "message": "The selected age_ratings.0 is invalid.",
  "errors": { "age_ratings.0": ["The selected age_ratings.0 is invalid."] }
}
```

## GET /api/movies/{id}

One movie with its genres and upcoming scheduled screenings, sorted by start time.
Unknown or non-numeric id → 404.

```json
{
  "data": {
    "id": 69,
    "title": "1917",
    "description": "Two young soldiers must cross enemy territory to deliver a message that can save 1600 men.",
    "duration_minutes": 119,
    "release_date": "2019-01-01",
    "age_rating": "16+",
    "poster_url": null,
    "trailer_url": null,
    "genres": [
      { "id": 28, "name": "Drama" },
      { "id": 97, "name": "War" }
    ],
    "screenings": [
      {
        "id": 36,
        "starts_at": "2026-10-29T15:37:00+00:00",
        "ends_at": "2026-10-29T17:36:00+00:00",
        "price": "9.89",
        "language": "en",
        "subtitles": null,
        "format": "2D",
        "hall": {
          "id": 12,
          "name": "Hall 1",
          "cinema": { "id": 5, "name": "Waters and Sons Cinema", "city": "West Veldaville" }
        }
      }
    ]
  }
}
```

## GET /api/genres

Genres for the genre filter, sorted by name. Only genres that have at least
one movie, so the filter never offers an option with no results.

```json
{
  "data": [
    { "id": 1, "name": "Action" },
    { "id": 3, "name": "Adventure" }
  ]
}
```
