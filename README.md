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