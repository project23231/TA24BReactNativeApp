# KinoPacanjata

Cinema project by Maksim Kalinski and Jaromir Kuras.

## Backend choice

We use PHP with Laravel and its Eloquent ORM. Laravel provides migrations
for creating the database structure and models for working with related
records. Its factories and seeders work with Faker to generate demo data.

## Requirements

- Docker Desktop
- PHP 8.5 with pdo_pgsql and intl enabled
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

PostgreSQL uses the named volume postgres_data.
The volume keeps the data when the container is removed.
Do not use docker compose down -v if you want to keep the data.

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
- 100 cinemas and 100 halls
- 10,000 seats
- 100 movies and 100 test genres
- 100–300 movie–genre links
- 100 users
- 100 screenings
- 100 bookings
- 100 tickets

Genre names are artificial demo values.
Related records are created using existing database IDs.

Run the seeders once on a fresh database.
They are not designed to be rerun on an already populated database.

### 4. Start Laravel

From the backend directory:

```bash
php artisan serve
```

Open http://127.0.0.1:8000.
Currently, this displays the default Laravel page.

## Database structure

project.sql contains the exported application schema.
Laravel's internal migrations table is excluded.

The schema includes changes made during the Laravel stage:
users has created_at and updated_at, and roles and statuses use
restricted values defined through Laravel's enum method.

For a new installation, use migrations rather than importing project.sql
and then running the same migrations.

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
and cinema. Dates are interpreted in the Europe/Tallinn time zone.

When data changes, clear all affected cache variants after the
database transaction commits. For a rescheduled screening,
clear both the old and new date/cinema variants.

Seat availability will not be cached initially.
Booking and ticket changes must be checked against current database
data. The database unique index remains responsible for preventing
two valid tickets for the same screening and seat.

A ticket booking does not require clearing the schedule cache
because that cache contains no seat counts or availability.

We plan to use Laravel Cache with the file store first.
Redis can be added later by configuring a Redis connection and
changing the cache store.