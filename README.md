# Apollo Kino

Cinema website project by Maksim Kalinski and Jaromir Kuras
(Tallinna Polütehnikum, K-TA-24B).

- API: [docs/api.md](docs/api.md)
- Customer journey: [docs/customer-journey.md](docs/customer-journey.md)
- Project log: [LOG.md](LOG.md)
- Frontend: [frontend/README.md](frontend/README.md)

## Backend choice

We use PHP with Laravel and its Eloquent ORM. Laravel provides migrations
for creating the database structure and models for working with related
records. Its factories and seeders work with Faker to generate demo data.

## Requirements

- Docker Desktop
- PHP 8.3 or newer with pdo_pgsql and intl enabled
- Composer
- Git

The database runs in Docker. Laravel runs locally.
Run the commands below in Git Bash.

## First-time setup

### 1. Start PostgreSQL

From the project root, copy the example configuration:

```bash
cp .env.example .env
```

Set DB_PASSWORD in this .env file to your local database password.

Start the database service:

```bash
docker compose up -d database
```

PostgreSQL uses the named volume postgres_data
(Docker shows it as kino_postgres_data).
The volume keeps the data when the container is removed.
Do not use docker compose down -v if you want to keep the data.

The compose project used to be called kinoshka_epta. After the rename
Docker creates a new, empty volume kino_postgres_data. If you set the
project up before the rename, run the createdb command below and
`php artisan migrate:fresh --seed` again. The old volume can then be
removed with `docker volume rm kinoshka_epta_postgres_data`.

Create the Laravel development database:

```bash
docker compose exec database createdb -U postgres kino_dev
```

Run this command only once. Skip it if kino_dev already exists.
The original kino database is kept separately.

### 2. Configure Laravel

```bash
cd backend
composer install
cp .env.example .env
```

In backend/.env, set DB_PASSWORD to the same password used for PostgreSQL.

The database settings should be:

```ini
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=kino_dev
DB_USERNAME=postgres
DB_PASSWORD=your_local_password
```

Keep these settings:

```ini
SESSION_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
```

Generate the application key and clear cached configuration:

```bash
php artisan key:generate
php artisan config:clear
```

Do not commit either .env file.

### 3. Create tables and demo data

On an empty kino_dev database, run:

```bash
php artisan migrate
php artisan db:seed
```

Migrations create the tables and constraints.
DatabaseSeeder runs the individual seeders in dependency order.

The seeders create:
- 100 cinemas with 2–3 halls each (Hall 1, Hall 2, ...)
- 100 seats per hall (10 rows × 10 seats), inserted in one query per hall
- 100 real genres and 100 real movies with their real genres
  (lists in backend/database/data/genres.php and movies.php)
- 100 users
- 2–4 screenings per hall on one day within the next 14 days,
  one after another with a 20-minute break, so they never overlap
- one booking per screening
- 1–4 tickets per booking, on free seats of the screening's hall

Only the release year of the movies is real, the day is always 1 January.
Cinemas, users and screenings are random demo data.
Related records are created using existing database IDs.

Run the seeders once on a fresh database.
They are not designed to be rerun on an already populated database.

### 4. Start Laravel

From the backend directory:

```bash
php artisan serve
```

Open http://127.0.0.1:8000/api/movies.
The API is described in [docs/api.md](docs/api.md).

## Database structure

project.sql contains the exported application schema.
Laravel's internal migrations table is excluded.

The schema includes changes made during the Laravel stage:
users has created_at and updated_at, and roles and statuses use
restricted values defined through Laravel's enum method.

For a new installation, use migrations rather than importing project.sql
and then running the same migrations.

Constraints worth knowing:

- A seat can have only one valid ticket per screening
  (partial unique index tickets_valid_seat_unique).
- A ticket's seat and screening must be in the same hall
  (composite foreign keys on tickets).
- Two scheduled screenings in one hall cannot overlap in time
  (exclusion constraint screenings_no_overlap, needs the btree_gist extension).
- Hall names are unique within a cinema.
- age_rating is one of 0+, 6+, 12+, 16+, 18+.
- bookings.cancelled_at can be set only on cancelled bookings.
- Deleting a movie or genre deletes its movie_genres links.
  All other foreign keys keep the default NO ACTION, so movies, screenings,
  seats and users with bookings or tickets cannot be deleted by accident.

## Rebuild demo data

Only for the disposable kino_dev development database:

```bash
php artisan migrate:fresh --seed
```

This deletes all tables and their data in the configured database,
then recreates and seeds them.

## Query indexes

These indexes support the planned movie list, filters and schedule pages.
They are created through a Laravel migration.

- movies(release_date): supports sorting movies by release date.
- movie_genres(genre_id, movie_id): supports finding movies of a selected genre.
- halls(cinema_id): supports finding halls in a selected cinema.
- screenings(starts_at): supports filtering screenings by a time range and sorting by start time.
- screenings(hall_id, starts_at): supports finding screenings in a selected hall within a time range.

PostgreSQL does not index foreign keys automatically. These indexes
support the model relations and foreign-key checks when a parent row
is deleted:

- screenings(movie_id): screenings of a movie.
- bookings(user_id): "my bookings" page.
- bookings(screening_id): bookings of a screening.
- tickets(booking_id): tickets in a booking.
- tickets(seat_id): tickets for a seat, checked when a seat is deleted.
- tickets(screening_id): all tickets of a screening. The partial unique
  index tickets_valid_seat_unique covers only valid tickets.

Existing primary-key and unique indexes are reused.

Date filters will use start and end timestamps, for example:
starts_at >= day_start AND starts_at < next_day_start.
This lets the query use the index without applying a date function
to the indexed column.

An ordinary B-tree index on the title is not added for substring
search such as ILIKE '%home%'.

Index performance has not been benchmarked yet.
With small tables, PostgreSQL may choose a table scan.

## Caching plan

This is a plan. Query-result caching is not implemented yet.

| Cached result | Lifetime | When to clear it |
|---|---|---|
| First three pages of the movie list | 5 minutes | After adding, editing or deleting a movie, or changing its genres |
| Movie details with genres | 10 minutes | After changing or deleting that movie, its genre links or genre names |
| Genre list | 1 hour | After adding, editing or deleting a genre |
| Screenings for a selected date and cinema, without seat availability | 1 minute | After adding, editing, cancelling or deleting a screening, or changing displayed movie, hall or cinema details |

Movie-list cache keys must include page number, search text,
filters and sorting. Schedule keys must include the selected date
and cinema.

### Clearing many key variants with the file store

There can be any number of movie-list keys (every page, search text
and filter combination), and the file store cannot list them or use
`Cache::tags()`. So each group of keys contains a version number.
Changing the data increases the version, and all old keys stop being
used at once. They expire on their own after their lifetime.

```php
$v = Cache::rememberForever('movies:version', fn () => 1);
$key = "movies:v{$v}:page={$page}:q={$q}:genre={$genre}:sort={$sort}";

// after a movie or genre change:
Cache::increment('movies:version');
```

Schedule keys work the same way with their own version
(`schedule:v{$v}:{date}:{cinema}`). When we switch to Redis,
tags can replace the version numbers.

### When the cache is cleared

The version is increased only after the database transaction commits,
never inside it. Otherwise another request could cache the old data
again before the commit. We will use model observers with
`public $afterCommit = true;` (or `DB::afterCommit(...)` in a service).

### Time zone

The application stores and compares times in UTC
(`'timezone' => 'UTC'` in backend/config/app.php), and the database
columns are timestampTz. A selected date is turned into a time range in
the Europe/Tallinn time zone before querying:

```php
$start = Carbon::parse($date, 'Europe/Tallinn')->startOfDay();
$end = $start->copy()->addDay();
// where starts_at >= $start and starts_at < $end
```

Without this, screenings between 00:00 and 03:00 local time would be
counted on the wrong day.

Seat availability will not be cached initially.
Booking and ticket changes must be checked against current database
data. The database unique index remains responsible for preventing
two valid tickets for the same screening and seat.

A ticket booking does not require clearing the schedule cache
because that cache contains no seat counts or availability.

We plan to use Laravel Cache with the file store first.
Redis can be added later by configuring a Redis connection and
changing the cache store.
## KIN-1: React movie listings

Start Laravel with `php artisan serve` from `backend/`. From `frontend/`, run
`npm ci` and `npm run dev`, then open http://localhost:5173. Vite forwards `/api`
to Laravel on port 8000. Copy `frontend/.env.example` to `frontend/.env` to change
`API_PROXY_TARGET`; inside Docker use `http://host.docker.internal:8000` when
Laravel runs on the Windows host. Production hosting must also forward `/api`
to Laravel and serve `index.html` for frontend routes.

The public React pages use custom CSS. Movie cards load from the paginated API,
show genres, runtime and age ratings, and handle missing/broken posters,
loading, errors, empty results and pagination. `/afisha.html` redirects to `/`.

## Integration tests

Tests use a separate PostgreSQL database named kino_test with the credentials
from backend/.env. Create it once, run migrations with DB_DATABASE=kino_test,
then run php vendor/bin/phpunit from backend/. Test fixtures roll back after
each test; the development database is never reset.


## KIN-5: Session schedule

Open `/schedule` in the frontend. Select a date and optional cinema, or use
"View screenings" on a movie card to show just that film. Times and dates use
Europe/Tallinn regardless of the visitor's computer timezone. Prices are EUR.
The schedule excludes cancelled/already started screenings and supports
pagination, retries and empty results. The database must contain future
screenings; the supplied ScreeningSeeder creates dates across the next 14 days.
No booking or payment action is included in this feature.
