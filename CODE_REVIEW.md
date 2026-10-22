# Code review 22.10

> Issues are switched off in this repository, so this review comes as a pull request instead of GitHub issues (see [item 12](#issue-12)). Each numbered section below is written so it can be copied into an issue as it is.

This is a review of `project23231/TA24BReactNativeApp`, branch `master` at commit `ca75b3b` (merge of PR #2), plus the unmerged branch `jaromir-kuras`. It reviews the current state only, not features that are still to come. A missing API or booking page is expected at this stage and is not counted against you. Each issue below has file:line references and a concrete fix. I ran `php artisan migrate` and `db:seed` on a clean PostgreSQL 17 to check things.

## What the project has now

- Laravel 13 backend (`backend/`) with Eloquent models for cinemas, halls, seats, movies, genres, screenings, bookings, tickets and users, with all relations defined.
- 11 migrations, including a separate migration for query indexes, CHECK constraints and a partial unique index so a seat can't be sold twice for one screening.
- Factories and seeders that create 100+ rows per table in dependency order. `migrate:fresh --seed` runs without errors.
- `project.sql` schema export and `diagram.png` ER diagram.
- A README with working setup steps, an explanation of each index and a caching plan.
- PostgreSQL 17 in Docker Compose with a named volume. A React 19 + Vite + Mantine frontend skeleton and a static HTML/CSS movie page (`public/afisha.html`).

## What's done well

- **The data model is genuinely good.** Composite foreign keys `tickets(seat_id, hall_id) -> seats(id, hall_id)` and `tickets(screening_id, hall_id) -> screenings(id, hall_id)` guarantee that a ticket's seat is in the same hall as the screening. Most teams don't think of this.
- `tickets_valid_seat_unique ... WHERE status = 'valid'` solves "sold twice" correctly while still allowing a cancelled seat to be resold.
- Money is `decimal(10,2)`, screening times are `timestampTz`, `ends_at > starts_at` and positive price/duration are enforced in the database. Ticket price is stored per ticket (the price at the time of sale).
- The `User` model maps Laravel auth onto your own `password_hash` column with a `hashed` cast, which is a tidy solution.
- The index README section explains *why* each index exists and warns that small tables may still be scanned.
- The caching plan avoids caching seat availability and clears the cache after commit. Those are the right instincts.
- Recent commit messages (`62f2cf4`, `63ee48b`) are clear and descriptive.

## Issues by priority

**Bugs** (fix what exists now):
- [1. docker-compose "backend" service serves backend/.env as a plain file and does not run Laravel](#issue-1)
- [2. users migration: duplicate role CHECK, timestamps without time zone, missing DB import](#issue-2)
- [3. project.sql is out of date with the migrations](#issue-3)
- [4. Seed data: all screenings start at the same moment, seat seeding takes ~50 s, some factories cannot be used alone](#issue-4)

**Improvements**:
- [5. Add indexes on foreign key columns that the model relations already query](#issue-5)
- [6. Schema: make delete behaviour explicit and tighten a few constraints](#issue-6)
- [7. Caching plan: invalidating "all variants" will not work with the file store as described](#issue-7)
- [8. Code style: run Pint, fix indentation in models/migrations, remove slang comments](#issue-8)
- [9. Frontend: remove Vite leftovers and connect or retire the static afisha.html](#issue-9)
- [10. README and docs: PHP version mismatch, default READMEs, LOG.md formatting](#issue-10)
- [11. Git practice: commit messages, commit size and how branches/PRs are used](#issue-11)
- [12. Turn on GitHub Issues for this repository](#issue-12)

<a id="issue-1"></a>

## 1. docker-compose "backend" service serves backend/.env as a plain file and does not run Laravel

*Label: bug*

### Problem

`docker-compose.yml:12-19` defines:

```yaml
backend:
  image: php:8.4-cli
  volumes:
    - ./backend:/app
  ports:
    - "8000:8000"
  command: php -S 0.0.0.0:8000 -t /app
```

This was written for the old `hello.php` test (now `docs/examples/hello.php`). Since Laravel was added, the document root `/app` is the whole Laravel project, not `backend/public`. PHP's built-in server returns any existing file as-is, so after `docker compose up` these URLs work:

- `http://localhost:8000/.env` returns your real `DB_PASSWORD` and `APP_KEY`
- `http://localhost:8000/composer.json`, `/artisan`, `/storage/logs/laravel.log` etc.

At the same time `http://localhost:8000/` gives 404, because there is no `index.php` in `/app`. The `php:8.4-cli` image also has no `pdo_pgsql`, so Laravel could not reach PostgreSQL from this container anyway.

The README tells you to run only `docker compose up -d database` and then `php artisan serve` on port 8000 (README.md:36, 123-126). Anyone who types the usual `docker compose up` instead gets the leaking container, and `php artisan serve` then cannot use port 8000.

### Why it matters

A `.env` file should never be downloadable. It's harmless on a laptop today, but this habit ends up on a real server. Right now the compose file also describes a setup that does not work, which confuses anyone setting up the project from a clean clone.

### Suggested fix

Choose one option and make the README match it:

1. **Laravel runs locally (what the README says now):** delete the `backend` service from `docker-compose.yml`. The `database` service (and `frontend` if you use it) is enough.
2. **Laravel runs in Docker:** build a small image with the pgsql driver and serve `public/`:

```dockerfile
# backend/Dockerfile
FROM php:8.4-cli
RUN apt-get update && apt-get install -y libpq-dev unzip \
 && docker-php-ext-install pdo_pgsql
WORKDIR /app
```

```yaml
backend:
  build: ./backend
  volumes: ["./backend:/app"]
  ports: ["8000:8000"]
  environment:
    DB_HOST: database
  depends_on: [database]
  command: php artisan serve --host=0.0.0.0 --port=8000
```

`artisan serve` serves only `public/`, so `.env` cannot be downloaded.

<a id="issue-2"></a>

## 2. users migration: duplicate role CHECK, timestamps without time zone, missing DB import

*Label: bug*

### Problem

`backend/database/migrations/0001_01_01_000000_create_users_table.php` differs from the other migrations in three ways:

- [ ] **Role is checked twice** (lines 17, 21-25). `$table->enum('role', ['admin', 'customer'])` already creates a CHECK constraint in PostgreSQL (`users_role_check`). The extra `ALTER TABLE ... ADD CONSTRAINT user_role_valid CHECK (role IN (...))` creates a second, identical one. Both constraints appear in `project.sql:288-289` and in `\d users` after `php artisan migrate`. If a role is added later, both constraints have to change, or inserts will fail. **Fix:** delete the `DB::statement` block and let `enum()` handle it, as `bookings`, `screenings` and `tickets` already do.
- [ ] **Timestamps without time zone** (line 18). `$table->timestamps()` creates `timestamp(0) without time zone` (`project.sql:286-287`), while `bookings.created_at`, `screenings.starts_at/ends_at` use `timestampTz`. So one table stores local time and the others store absolute time. **Fix:** `$table->timestampsTz();`
- [ ] **`DB` is used without `use Illuminate\Support\Facades\DB;`** (line 21). It works only because Laravel registers a global `DB` alias. Every other migration that uses `DB::statement` imports it (e.g. `..._create_movies_table.php:5`). If you keep any raw SQL here, add the import.

### Why it matters

Duplicate constraints and mixed time types are easy to miss now and hard to clean up after real data exists. Timestamps are also the first thing that breaks when you compare "booking created at" with "screening starts at".

### Suggested fix

Since this is a development database, change the migration in place, then run `php artisan migrate:fresh --seed` and re-export `project.sql` (see the project.sql issue).

<a id="issue-3"></a>

## 3. project.sql is out of date with the migrations

*Label: bug, documentation*

### Problem

The README says `project.sql` "contains the exported application schema" and that the migrations table is excluded (README.md:131-132). The file does not match that:

- [ ] **The five new indexes are missing.** Commit `63ee48b` added `2026_10_15_095727_add_cinema_query_indexes.php` (`movies_release_date_index`, `movie_genres_genre_id_movie_id_index`, `halls_cinema_id_index`, `screenings_starts_at_index`, `screenings_hall_id_starts_at_index`), but `project.sql` was not re-exported. The only index it lists besides PK/unique ones is `tickets_valid_seat_unique` (project.sql:439).
- [ ] **A part of Laravel's migrations table is still there.** `CREATE SEQUENCE public.migrations_id_seq` (project.sql:131-141) was left behind when the table was removed.
- [ ] **The dump starts with `\restrict ...` and ends with `\unrestrict ...`** (project.sql:5, 559). These are psql meta-commands from new pg_dump versions. Older `psql` clients and most GUI tools (pgAdmin's query tool, DBeaver) will report a syntax error on line 5.

### Why it matters

The schema file is what reviewers and the teacher read to understand your database. If it disagrees with the migrations, nobody knows which one is correct.

### Suggested fix

Re-export after every migration change, from the migrated `kino_dev` database:

```bash
docker compose exec database pg_dump -U postgres --schema-only --no-owner \
  --exclude-table=migrations --exclude-table=migrations_id_seq kino_dev > project.sql
```

Remove the `\restrict` / `\unrestrict` lines if you want the file to open in any SQL tool. Consider adding "re-export project.sql" to your checklist for every migration commit, or note in the README which migration the file matches.

<a id="issue-4"></a>

## 4. Seed data: all screenings start at the same moment, seat seeding takes ~50 s, some factories cannot be used alone

*Label: bug*

### Problem

I ran `php artisan migrate:fresh --seed` on a clean PostgreSQL 17. It works, and the dependency order in `DatabaseSeeder` is correct. The data and factories still have some problems:

- [ ] **Every screening has the same `starts_at`** (`database/factories/ScreeningFactory.php:17`: `now()->addWeek()->setTime(18, 0)`). After seeding, `select count(distinct starts_at) from screenings` returns **1** for 100 rows. Your new indexes `screenings(starts_at)` and `screenings(hall_id, starts_at)` and the planned "schedule for a date" page cannot be tested with this data: a date filter returns all screenings or none. **Fix:** spread them out, e.g.
  ```php
  'starts_at' => fake()->dateTimeBetween('now', '+14 days')->setTime(fake()->numberBetween(10, 22), fake()->randomElement([0, 15, 30, 45])),
  ```
  and in `ScreeningSeeder` create several screenings per hall, one after another (start = previous end + 20 min), so halls never have overlapping screenings.
- [ ] **`SeatSeeder` takes about 52 of the ~60 seconds** (measured). It makes 10,000 separate `create()` calls (`database/seeders/SeatSeeder.php:13-21`), and each one is a separate INSERT. **Fix:** build the 100 rows per hall in an array and insert them in one query:
  ```php
  $rows = [];
  for ($r = 1; $r <= 10; $r++) for ($n = 1; $n <= 10; $n++)
      $rows[] = ['hall_id' => $hall->id, 'row_number' => $r, 'seat_number' => $n];
  Seat::insert($rows);
  ```
- [ ] **`TicketFactory` has only `price` and `status`** (`database/factories/TicketFactory.php:11-14`). `Ticket::factory()->create()` fails on the NOT NULL `booking_id`/`screening_id`/`seat_id`/`hall_id`. It is fine inside `TicketSeeder`, but a test that calls the factory directly will fail. Either give it sensible defaults or add a short comment that it must be used with explicit attributes.
- [ ] **`SeatFactory` always returns row 1, seat 1** (`database/factories/SeatFactory.php:14-15`). A second `Seat::factory()->for($hall)->create()` breaks the `(hall_id, row_number, seat_number)` unique constraint. Use `fake()->unique()->numberBetween(...)` or a `sequence()`.
- [ ] **One hall per cinema, one booking per screening, one ticket per booking.** That is enough for the 100-rows requirement, but it means queries like "how many seats are free" or "tickets in a booking" are never tested with more than one row. Consider 2-4 halls per cinema and 1-4 tickets per booking.

### Why it matters

Seed data is how you test your queries, indexes and later the UI. If every row looks the same, bugs in date filters and joins stay hidden.

### Suggested fix

See the items above. After the change, check with e.g. `select starts_at::date, count(*) from screenings group by 1 order by 1;`.

<a id="issue-5"></a>

## 5. Add indexes on foreign key columns that the model relations already query

*Label: enhancement*

### Problem

The new index migration (`2026_10_15_095727_add_cinema_query_indexes.php`) is well reasoned, and the README explains each index (README.md:152-174). PostgreSQL does **not** create an index for a foreign key automatically, though, and several FK columns that your Eloquent relations already query still have no usable index (checked with `pg_indexes` after migrating):

| Column | Used by | Covered now? |
|---|---|---|
| `screenings.movie_id` | `Movie::screenings()` (Movie.php:30) | no |
| `bookings.user_id` | `User::bookings()` (User.php:33) | no |
| `bookings.screening_id` | `Screening::bookings()` (Screening.php:47) | no, `unique(id, screening_id)` starts with `id` |
| `tickets.booking_id` | `Booking::tickets()` (Booking.php:32) | no |
| `tickets.seat_id` | `Seat::tickets()`, FK checks when a seat is deleted | no |
| `tickets.screening_id` | `Screening::tickets()` | only for `status = 'valid'` (partial index) |

Already fine: `seats.hall_id` (leading column of the unique index), `halls.cinema_id` and `screenings.hall_id` (new indexes), `movie_genres` both directions.

### Why it matters

"Show my bookings" and "tickets in this booking" are the main pages of a cinema site. Without these indexes each of them scans the whole table. Deleting or updating a parent row also scans the whole child table to check the FK.

### Suggested fix

Add a new migration (do not edit the already-merged one):

```php
Schema::table('screenings', fn (Blueprint $t) => $t->index('movie_id'));
Schema::table('bookings',   fn (Blueprint $t) => [$t->index('user_id'), $t->index('screening_id')]);
Schema::table('tickets',    fn (Blueprint $t) => [$t->index('booking_id'), $t->index('seat_id')]);
```

Add matching `dropIndex` calls in `down()` and add one line per index to the README table. When you benchmark, use `EXPLAIN ANALYZE` on the real queries after seeding more rows. With 100 rows PostgreSQL will usually scan the table anyway, which the README already notes.

<a id="issue-6"></a>

## 6. Schema: make delete behaviour explicit and tighten a few constraints

*Label: enhancement*

### Problem

The schema is already strong. The composite FKs that keep a ticket's seat and screening in the same hall, and the partial unique index `tickets_valid_seat_unique`, are better than most projects in the course. The remaining gaps:

- [ ] **No `onDelete` anywhere.** Every FK uses the default `NO ACTION`. For `bookings`/`tickets` that is correct (you should not silently delete sales history). For `movie_genres` it is not: deleting a movie that has genres fails with an FK error (`..._create_movie_genres_table.php:20-26`). Your caching plan already says "after ... deleting a movie" (README.md:182). **Fix:** `->cascadeOnDelete()` on both `movie_genres` FKs, and write `->restrictOnDelete()` on the others so the intent is visible in the code.
- [ ] **`movies.age_rating` is free text** (`..._create_movies_table.php:18`). The factory uses `0+ 6+ 12+ 16+ 18+`, but anything can be stored, e.g. the `3+` in `frontend/public/afisha.html:60`. You already use `enum()` for statuses. Do the same here: `$table->enum('age_rating', ['0+','6+','12+','16+','18+'])` (or whatever rating system you choose).
- [ ] **Hall names are not unique per cinema** (`..._create_halls_table.php:16`). `HallFactory` picks `'Hall ' . numberBetween(1, 10)`, so two "Hall 3" in one cinema are possible. Add `$table->unique(['cinema_id', 'name']);`.
- [ ] **Two screenings can overlap in the same hall.** Nothing stops a 18:00-20:00 and a 19:00-21:00 screening in hall 5. PostgreSQL can enforce this with an exclusion constraint (optional, a bit more advanced):
  ```sql
  CREATE EXTENSION IF NOT EXISTS btree_gist;
  ALTER TABLE screenings ADD CONSTRAINT screenings_no_overlap
    EXCLUDE USING gist (hall_id WITH =, tstzrange(starts_at, ends_at) WITH &&)
    WHERE (status = 'scheduled');
  ```
- [ ] **`bookings` has no record of when a booking was cancelled** (`..._create_bookings_table.php:19-20`). There is `created_at` and `Booking::UPDATED_AT = null`, so changing `status` to `cancelled` leaves no time. A nullable `cancelled_at timestampTz` is cheap and useful for refunds and reports.
- [ ] **`customer_email` has no format check.** Validation belongs in Laravel form requests later, but a basic `CHECK (customer_email LIKE '%_@_%')` keeps obviously broken data out of the database.

### Why it matters

Constraints are the last line of defence once there is an API and an admin panel. Explicit delete rules also make the ER diagram easier to read.

### Suggested fix

Put these in one new migration (e.g. `add_schema_constraints`), regenerate `diagram.png` and `project.sql`, and add a line to LOG.md.

<a id="issue-7"></a>

## 7. Caching plan: invalidating "all variants" will not work with the file store as described

*Label: enhancement, documentation*

### Problem

The caching plan in README.md:176-205 is well thought out. It doesn't cache seat availability, it says to clear the cache after commit, and it clears both old and new dates when a screening moves. Three details will cause trouble when you implement it:

- [ ] **You cannot list "all affected variants" in the file store.** Movie-list keys include page, search text, filters and sort (README.md:187-188), so there is an unlimited number of keys. The plan says to "clear all affected cache variants" (README.md:191), but Laravel's `file` store has no way to find them and does not support `Cache::tags()` (it throws "This cache store does not support tagging"). **Fix:** use a version number in the key:
  ```php
  $v = Cache::rememberForever('movies:version', fn () => 1);
  $key = "movies:v{$v}:page={$page}:q={$q}:genre={$genre}:sort={$sort}";
  // on any movie/genre change:
  Cache::increment('movies:version');
  ```
  Old keys simply expire after 5 minutes. The same works for `schedule:{date}:{cinema}`, or use tags once you switch to Redis.
- [ ] **The app timezone is UTC, the plan uses Europe/Tallinn.** `backend/config/app.php:68` is `'timezone' => 'UTC'`. Either set `APP_TIMEZONE`/`'timezone' => 'Europe/Tallinn'`, or always build day boundaries with `Carbon::parse($date, 'Europe/Tallinn')->startOfDay()`. Otherwise "screenings on 22.10" will miss the screenings between 00:00 and 03:00 local time.
- [ ] **"After the transaction commits" needs a concrete mechanism.** Write down that you will use model observers with `$afterCommit = true` (or `DB::afterCommit(fn () => ...)`), so the person implementing it does not clear the cache inside the transaction.

### Why it matters

These are the classic cache bugs (stale lists, wrong day). Fixing them in the plan now costs a few lines of text instead of a debugging session later.

### Suggested fix

Update the "Caching plan" section with the key-versioning approach and the timezone decision. Optionally add one small `Cache::remember` example for the genre list, since that is the simplest entry and needs no invalidation tricks.

<a id="issue-8"></a>

## 8. Code style: run Pint, fix indentation in models/migrations, remove slang comments

*Label: enhancement*

### Problem

Laravel Pint is already installed (`backend/composer.json:17`) but the code has clearly not been formatted with it:

- [ ] **Relation methods added later lost their indentation** in almost every model: `app/Models/Booking.php:32-35`, `Hall.php:23-34`, `Cinema.php:17-20`, `Movie.php:30-33`, `Screening.php:47-54`, `Seat.php:22-25`. Example:
  ```php
      public function tickets(): HasMany
  {
      return $this->hasMany(Ticket::class);
  }
  ```
  The same happens in migrations (`..._create_cinemas_table.php:15-18`, `..._create_movie_genres_table.php:24-26`) and `CinemaFactory.php:20-24`. Several files have no newline at the end.
- [ ] **`generatedAs('')`** is used for every id (e.g. `..._create_cinemas_table.php:15`). It works because the empty string is ignored, but `->generatedAs()` with no argument does the same and is clearer to read.
- [ ] **Slang and profanity in committed files.** `frontend/public/afisha.css:1,17,34,44` contain Russian swear-word comments, and the compose project is named `kinoshka_epta` (`docker-compose.yml:1`). This repository is your portfolio and teachers and future employers read it. Use comments that explain the CSS (`/* Movie card grid */`) and a neutral project name (`name: kino`).
- [ ] **Mixed languages.** The commit `88ec1dd`, LOG.md line 1 and the CSS comments are in Russian, everything else is in English. Choose one language for code, comments and commits (English is the usual choice).

### Why it matters

Consistent formatting makes diffs small and reviews easy. When half the file is misindented, real changes are hard to see.

### Suggested fix

```bash
cd backend
./vendor/bin/pint          # formats everything
./vendor/bin/pint --test   # use this before committing
```

Commit the Pint run as its own commit (`Format backend with Pint`) so it does not mix with real changes. For the frontend, `npm run lint` is already set up (`frontend/package.json:9`). Run it too.

<a id="issue-9"></a>

## 9. Frontend: remove Vite leftovers and connect or retire the static afisha.html

*Label: enhancement*

### Problem

The frontend has two separate pieces that do not use each other, plus unused files from the Vite starter:

- [ ] **Unused Vite starter files.** `frontend/src/App.css` (184 lines, `.counter`, `.hero` ...), `frontend/src/index.css`, `frontend/src/assets/hero.png`, `react.svg`, `vite.svg` are not imported anywhere (`main.jsx` imports only Mantine styles and `App.jsx`). `frontend/README.md` is the default "React + Vite" text. Delete them, or replace the README with a few lines about your frontend.
- [ ] **`frontend/public/afisha.html` is a separate static page.** The React app (`App.jsx`) shows "Hello World", and the movie list exists only as hand-written HTML in `public/`, reachable at `/afisha.html`. That was fine as the HTML/CSS exercise. When you build the movie list page, rebuild it as React components (`MovieCard`, `MovieList`) with Mantine and delete the static copy, so there is only one version to maintain.
- [ ] **Image file names with spaces** (`public/images/home alone.jpg`, `pirates of caribbean.jpg`, `chip and dale rescue rangers.jpg`). They work in the browser, but in URLs, shell commands and the future `movies.poster_url` column they have to be escaped. Rename to `home-alone.jpg` etc.
- [ ] **Demo data contradicts itself** (`afisha.html:37-60`). Pirates of the Caribbean is listed as "Science fiction", Chip and Dale has age rating `3+`, which your own rating list does not contain, and the descriptions are placeholders ("squirells"). If this page stays, make it believable. It is the first thing anyone opens.
- [ ] **Accessibility basics are OK.** Images have `alt`, headings are in order, there is a narrow-screen rule. Keep that when moving to React.

### Why it matters

Dead starter files and a second, disconnected page make it unclear which code is the real frontend.

### Suggested fix

One cleanup commit: `Remove unused Vite starter files`. Later, when the movie list is built in React, a second one: `Replace static afisha page with MovieList component`.

<a id="issue-10"></a>

## 10. README and docs: PHP version mismatch, default READMEs, LOG.md formatting

*Label: documentation, enhancement*

### Problem

The root README is clear, and its setup steps worked from a clean clone (tested in a PHP 8.4 container against PostgreSQL 17: `composer install`, `migrate`, `db:seed`). Some smaller inconsistencies remain:

- [ ] **Three different PHP versions.** README.md:14 says "PHP 8.5", `backend/composer.json:9` requires `^8.3`, and `docker-compose.yml:20` uses `php:8.4-cli`. Write the minimum that actually works (`PHP 8.3+`) and keep compose consistent.
- [ ] **`backend/README.md` is the default Laravel README** (logo, "About Laravel", sponsors). Delete it or replace it with a short pointer to the root README.
- [ ] **`docs/examples/hello.php`** is the old PHP test moved out of `backend/` in `1547b83`. Nothing uses it. Delete it.
- [ ] **LOG.md renders as one paragraph.** Entries are separated by single newlines, which Markdown joins together. Use a list (`- 2026-10-01 — ...`) or a table. The last entry is from 01.10. The index and caching work from 15.10 (`63ee48b`) is not logged, and the log should cover the whole team's work, not one person's.
- [ ] **Customer journey document only exists as `Apollo_Kino_kasutaja_teekond (2).docx`** on an unmerged branch (`a2c3614`). A `.docx` cannot be diffed or read on GitHub. Convert it to `docs/customer-journey.md` (drop the "(2)" and the spaces) and merge it into master through a PR.
- [ ] **`ReactNative.md` and the "react native" `.gitignore` entries** (`/ios`, `/android`, `.expo/`) come from the class template, but the project is now React + Vite + Laravel. Consider renaming the repo/README title so it's clear what the project is (the README title is "KinoPacanjata").

### Why it matters

Someone opening the repo first reads the README and LOG. When they contradict each other or the files, it looks like the project is abandoned, even when it isn't.

### Suggested fix

One documentation commit that addresses the checklist above.

<a id="issue-11"></a>

## 11. Git practice: commit messages, commit size and how branches/PRs are used

*Label: enhancement, documentation*

### Problem

Recent commits are much better than the early ones. `62f2cf4 Add cinema database schema and ER diagram, update project log with database work` and `63ee48b Add query indexes and document caching plan` are good messages. Things to improve:

- [ ] **Vague or non-English messages.** `97f1419 log,commited` (says nothing about what changed in LOG.md), `88ec1dd Добавил основу кинотеатра: React, PHP и Docker` (Russian, past tense), `a2c3614 Customer journey` (what about it? added? updated?).
- [ ] **Huge mixed commits.** `1547b83` changes 89 files / 11,552 lines: the generated Laravel skeleton (config, composer.lock, public/, storage/ ...) together with your own models, migrations, factories and seeders, plus moving `hello.php`. It's impossible to review what *you* wrote. `88ec1dd` similarly mixes the Vite scaffold, docker-compose, `.gitignore` and `.env.example`. `e7421a6 Document backend setup and sync database schema` also adds a new `Screening::tickets()` relation that the message does not mention.
- [ ] **Pull requests with no description or review.** PR #1 is titled with the branch name and has an empty description. Both PRs were merged by the same account that opened them, 10 and 23 seconds after opening, with no review from the other team member. Use PRs for what they're for: the other person reads the diff and approves it.
- [ ] **Branches are named per person and live forever.** `maksim-kalinski` is reused for every task. `jaromir-kuras` has one commit (`a2c3614`), branched from an old master (`dde0e53`), and was never merged. Use short-lived feature branches (`feature/query-indexes`, `docs/customer-journey`), one per task, delete after merge, and merge the customer-journey work.
- [ ] **Committed binary that should be text.** `Apollo_Kino_kasutaja_teekond (2).docx` (a2c3614) can't be diffed. See the docs issue.

### How to write a good message

- One logical change per commit. Commit the generated framework first (`Create Laravel 13 project in backend/`), then your own work (`Add cinema migrations and models`).
- Subject in English, imperative mood, at most ~60 characters, no period: "Add", "Fix", "Remove", not "Added"/"Добавил".
- Say *what* changed in the subject. If the reason isn't obvious, explain *why* in the body after an empty line.

Good examples for this repo:

```
Add unique constraint on hall name per cinema
Index foreign keys used by booking relations
Fix compose backend serving the project root
Remove unused Vite starter files

Seed screenings across two weeks

All screenings started at the same time, so date filters
could not be tested.
```

### Why it matters

Your git history is part of the grade and part of your portfolio. Small, clearly named commits also make `git bisect` and code review possible.

<a id="issue-12"></a>

## 12. Turn on GitHub Issues for this repository

*Label: enhancement*

### Problem
Issues are switched off in this repository (Settings → General → Features → Issues is unchecked). That is why this review arrives as a pull request with a Markdown file instead of as separate GitHub issues.

### Why it matters
Issues are where a team keeps track of bugs and tasks, assigns them to people and closes them from commits or pull requests (`Fixes #3`). Without them, feedback ends up in chat messages or files and is easy to lose, and there is no shared to-do list for the two of you.

### Suggested fix
- Repository owner: open **Settings → General → Features** and tick **Issues**.
- Then turn each section of `CODE_REVIEW.md` into an issue (copy the title and body), and close them from your commits as you fix them.
- Once all review items are issues, `CODE_REVIEW.md` can be deleted.
