WITH period AS (
    SELECT (now() AT TIME ZONE 'Europe/Tallinn')::date + 1 AS test_date
), day_screenings AS (
    SELECT s.*, h.cinema_id
    FROM screenings AS s
    JOIN halls AS h ON h.id = s.hall_id
    CROSS JOIN period AS p
    WHERE s.starts_at >= p.test_date::timestamp AT TIME ZONE 'Europe/Tallinn'
      AND s.starts_at < (p.test_date + 1)::timestamp AT TIME ZONE 'Europe/Tallinn'
      AND s.status = 'scheduled'
)
SELECT json_build_object(
    'generated_at', now(),
    'date', p.test_date,
    'starts_at', p.test_date::timestamp AT TIME ZONE 'Europe/Tallinn',
    'ends_at', (p.test_date + 1)::timestamp AT TIME ZONE 'Europe/Tallinn',
    'cinema_id', 1,
    'movie_id', 1,
    'totals', json_build_object(
        'day', (SELECT count(*) FROM day_screenings),
        'cinema', (SELECT count(*) FROM day_screenings WHERE cinema_id = 1),
        'movie', (SELECT count(*) FROM day_screenings WHERE movie_id = 1)
    ),
    'rows', json_build_object(
        'cinemas', (SELECT count(*) FROM cinemas),
        'halls', (SELECT count(*) FROM halls),
        'seats', (SELECT count(*) FROM seats),
        'movies', (SELECT count(*) FROM movies),
        'genres', (SELECT count(*) FROM genres),
        'movie_genres', (SELECT count(*) FROM movie_genres),
        'users', (SELECT count(*) FROM users),
        'screenings', (SELECT count(*) FROM screenings),
        'bookings', (SELECT count(*) FROM bookings),
        'tickets', (SELECT count(*) FROM tickets)
    )
)
FROM period AS p;
