# Screening schedule benchmark with pgbench

[pgbench](https://www.postgresql.org/docs/17/pgbench.html) is PostgreSQL's load
testing tool. It runs a custom SQL script with
several concurrent clients and reports transactions per second (TPS), average
latency and failed transactions. Here each transaction is one schedule query.

## Run locally

Install Docker Desktop with the Linux engine on Windows, or Docker Engine on
Linux. The `postgres:17` image provides both PostgreSQL and `pgbench`. Run from
the repository root in Git Bash on Windows or Bash on Linux:

```bash
bash bench/run-pgbench.sh
```

The script starts a disposable local PostgreSQL container with networking
disabled, restores the project's schema from `project.sql`, and adds 10,000
screenings and 50,000 tickets using `bench/seed-screenings.sql`. It removes the
container when it exits. The seed script expects an empty copy of the project
schema. The project's development database can keep running independently.

## Scenario

Eight concurrent clients browse the first 30 scheduled screenings on the same
future date. `bench/pgbench-screenings.sql` follows the main screening query in
Laravel's `ScheduleController`. It filters by the start and end of a Tallinn
calendar day, excludes cancelled screenings, and sorts by start time and ID.
The benchmark measures this SQL query alone. Laravel also loads related records
and computes pagination metadata when serving the HTTP endpoint.

The seed places 80 screenings on each day, of which 72 are scheduled. The
runner alternates between the original `screenings_starts_at_index` from the
index migration and the same database without that index. Other indexes and
constraints stay in place. Each variant gets three 10-second runs with a
3-second warm-up before each run, using eight clients on two pgbench threads.
The runner also prints `EXPLAIN (ANALYZE, BUFFERS)` for each variant.

## Results

Measured on 26 November 2026 with PostgreSQL and pgbench 17.11 in Docker Desktop
on Windows. The selected Tallinn screening date was 26 December 2026.
TPS excludes initial connection setup. Average latency is per run.

| Run | Index on `starts_at` | Completed queries | TPS | Average latency (ms) | Failed transactions |
| --- | --- | ---: | ---: | ---: | ---: |
| 1 | Present | 143,974 | 14,407.83 | 0.555 | 0 |
| 1 | Removed | 20,304 | 2,031.56 | 3.938 | 0 |
| 2 | Present | 121,200 | 12,130.96 | 0.659 | 0 |
| 2 | Removed | 19,435 | 1,945.55 | 4.112 | 0 |
| 3 | Present | 119,461 | 11,951.76 | 0.669 | 0 |
| 3 | Removed | 23,423 | 2,343.74 | 3.413 | 0 |

## Conclusion

The existing index raised median throughput from 2,031.56 to 12,130.96 TPS
and cut the median of the run averages from 3.938 to 0.659 ms. Without it,
PostgreSQL scanned the entire screenings table, discarded 9,928 rows and
sorted the matches. With the index, it scanned the requested time range and
stopped after finding enough rows for the first page. The two captured plans
showed 120 and 12 shared buffer hits, respectively. Keep the `starts_at`
index for the schedule query.
