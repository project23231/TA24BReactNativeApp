BEGIN;

WITH cinema AS (
    INSERT INTO cinemas (name, city, address)
    VALUES ('Benchmark Cinema', 'Tallinn', '1 Benchmark Street')
    RETURNING id
)
INSERT INTO halls (cinema_id, name)
SELECT cinema.id, 'Hall ' || hall_number
FROM cinema CROSS JOIN generate_series(1, 20) AS hall_number;

INSERT INTO movies (title, description, duration_minutes, age_rating)
SELECT 'Benchmark Film ' || film_number, 'Synthetic benchmark film', 100, '12+'
FROM generate_series(1, 20) AS film_number;

INSERT INTO seats (hall_id, row_number, seat_number)
SELECT halls.id, 1, seat_number
FROM halls CROSS JOIN generate_series(1, 5) AS seat_number;

WITH schedule AS (
    SELECT
        halls.id AS hall_id,
        movies.id AS movie_id,
        (
            (CURRENT_TIMESTAMP AT TIME ZONE 'Europe/Tallinn')::date + day_number
            + make_time(10 + slot * 3, 0, 0)
        ) AT TIME ZONE 'Europe/Tallinn' AS starts_at,
        CASE WHEN (day_number + slot + halls.id) % 10 = 0
            THEN 'cancelled' ELSE 'scheduled' END AS status
    FROM halls
    JOIN movies ON movies.id = halls.id
    CROSS JOIN generate_series(1, 125) AS day_number
    CROSS JOIN generate_series(0, 3) AS slot
)
INSERT INTO screenings (movie_id, hall_id, starts_at, ends_at, price, language, format, status)
SELECT movie_id, hall_id, starts_at, starts_at + interval '100 minutes', 10, 'en', '2D', status
FROM schedule;

WITH seed_bookings AS (
    INSERT INTO bookings (screening_id, customer_email, status, cancelled_at)
    SELECT id, 'benchmark@example.invalid',
           CASE WHEN status = 'cancelled' THEN 'cancelled' ELSE 'confirmed' END,
           CASE WHEN status = 'cancelled' THEN CURRENT_TIMESTAMP END
    FROM screenings
    RETURNING id, screening_id, status
)
INSERT INTO tickets (booking_id, screening_id, seat_id, price, status, hall_id)
SELECT seed_bookings.id, screenings.id, seats.id, screenings.price,
       CASE WHEN seed_bookings.status = 'cancelled' THEN 'cancelled' ELSE 'valid' END,
       screenings.hall_id
FROM seed_bookings
JOIN screenings ON screenings.id = seed_bookings.screening_id
JOIN seats ON seats.hall_id = screenings.hall_id;

COMMIT;

VACUUM ANALYZE;

SELECT (SELECT count(*) FROM screenings) AS screenings,
       (SELECT count(*) FROM tickets) AS tickets;
