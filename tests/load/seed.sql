\set ON_ERROR_STOP on

BEGIN;

-- Rebuilding these fixtures is restricted to the stand's disposable database.
DO $$
BEGIN
    IF current_database() <> 'kino_load' THEN
        RAISE EXCEPTION 'Load fixtures require the dedicated kino_load database';
    END IF;
END $$;

TRUNCATE TABLE tickets, bookings, screenings, movie_genres, genres,
    movies, seats, halls, cinemas, users RESTART IDENTITY;

INSERT INTO cinemas (id, name, city, address)
SELECT i, 'Benchmark Cinema ' || i,
    CASE WHEN i % 2 = 0 THEN 'Tallinn' ELSE 'Tartu' END,
    'Test Street ' || i
FROM generate_series(1, 20) AS i;

INSERT INTO halls (id, cinema_id, name)
SELECT i, ((i - 1) / 5) + 1, 'Hall ' || (((i - 1) % 5) + 1)
FROM generate_series(1, 100) AS i;

INSERT INTO seats (id, hall_id, row_number, seat_number)
SELECT (h - 1) * 100 + position, h,
    ((position - 1) / 10) + 1, ((position - 1) % 10) + 1
FROM generate_series(1, 100) AS h
CROSS JOIN generate_series(1, 100) AS position;

INSERT INTO genres (id, name)
SELECT i, 'Benchmark Genre ' || i FROM generate_series(1, 10) AS i;

INSERT INTO movies (id, title, description, duration_minutes, release_date, age_rating)
SELECT i, 'Benchmark Movie ' || i,
    'A synthetic film used to measure cinema schedule requests.',
    100, CURRENT_DATE - 30, '12+'
FROM generate_series(1, 100) AS i;

INSERT INTO movie_genres (movie_id, genre_id)
SELECT i, ((i - 1) % 10) + 1 FROM generate_series(1, 100) AS i
UNION ALL
SELECT i, (i % 10) + 1 FROM generate_series(1, 100) AS i;

INSERT INTO users (id, name, email, password_hash, role, created_at, updated_at)
SELECT i, 'Test Customer ' || i, 'customer' || i || '@example.com',
    '$2y$12$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi', 'customer', now(), now()
FROM generate_series(1, 100) AS i;

-- 20 days x 100 halls x 5 non-overlapping slots = 10,000 screenings.
-- Each movie has 5 screenings per day and each cinema has 25.
INSERT INTO screenings (id, movie_id, hall_id, starts_at, ends_at,
    price, language, subtitles, format, status)
SELECT (day_number * 500) + (slot * 100) + hall,
    ((hall - 1 + day_number + slot) % 100) + 1,
    hall,
    ((now() AT TIME ZONE 'Europe/Tallinn')::date + day_number + 1
        + make_time(8 + slot * 3, 0, 0)) AT TIME ZONE 'Europe/Tallinn',
    (((now() AT TIME ZONE 'Europe/Tallinn')::date + day_number + 1
        + make_time(8 + slot * 3, 0, 0)) AT TIME ZONE 'Europe/Tallinn') + interval '100 minutes',
    8.00 + (hall % 5), 'en', 'et', '2D', 'scheduled'
FROM generate_series(0, 19) AS day_number
CROSS JOIN generate_series(0, 4) AS slot
CROSS JOIN generate_series(1, 100) AS hall;

INSERT INTO bookings (id, user_id, screening_id, customer_email, status, created_at)
SELECT id, ((id - 1) % 100) + 1, id,
    'customer' || (((id - 1) % 100) + 1) || '@example.com', 'confirmed', now()
FROM screenings;

INSERT INTO tickets (id, booking_id, screening_id, seat_id, price, status, hall_id)
SELECT (s.id - 1) * 5 + position, s.id, s.id,
    (s.hall_id - 1) * 100 + position, s.price, 'valid', s.hall_id
FROM screenings AS s CROSS JOIN generate_series(1, 5) AS position;

-- Explicit fixture ids must also advance the identity sequences.
DO $$
DECLARE
    table_name text;
BEGIN
    FOREACH table_name IN ARRAY ARRAY['cinemas', 'halls', 'seats', 'genres',
        'movies', 'users', 'screenings', 'bookings', 'tickets']
    LOOP
        EXECUTE format('SELECT setval(pg_get_serial_sequence(%L, ''id''), max(id)) FROM %I',
            table_name, table_name);
    END LOOP;
END $$;

COMMIT;
ANALYZE;
