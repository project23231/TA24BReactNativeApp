SELECT id, movie_id, hall_id, starts_at, ends_at, price, language, subtitles, format, status
FROM screenings
WHERE status = 'scheduled'
  AND starts_at >= (:screening_day::date::timestamp AT TIME ZONE 'Europe/Tallinn')
  AND starts_at < ((:screening_day::date + 1)::timestamp AT TIME ZONE 'Europe/Tallinn')
ORDER BY starts_at, id
LIMIT 30;
