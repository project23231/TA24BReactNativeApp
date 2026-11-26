#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")/.."

docker_host=${DOCKER_HOST:-$(docker context inspect --format '{{.Endpoints.docker.Host}}')}
case "$docker_host" in
    npipe:*|unix:*) ;;
    *) echo 'Use a local Docker engine for this benchmark.' >&2; exit 1 ;;
esac

container=$(docker run --detach --rm --network none \
    --env POSTGRES_HOST_AUTH_METHOD=trust --env POSTGRES_DB=kino_pgbench postgres:17)
trap 'docker rm --force "$container" >/dev/null' EXIT

for ((attempt = 1; attempt <= 60; attempt++)); do
    if docker exec "$container" pg_isready -h 127.0.0.1 -U postgres -d kino_pgbench >/dev/null; then
        break
    fi
    if ((attempt == 60)); then
        docker logs "$container" >&2
        exit 1
    fi
    sleep 1
done

run_psql() {
    docker exec -i "$container" psql -X -U postgres -d kino_pgbench -v ON_ERROR_STOP=1 "$@"
}

run_psql --quiet < project.sql
run_psql < bench/seed-screenings.sql
docker exec -i "$container" sh -c 'cat > /tmp/pgbench-screenings.sql' < bench/pgbench-screenings.sql

screening_day=$(run_psql -Atc "SELECT (CURRENT_TIMESTAMP AT TIME ZONE 'Europe/Tallinn')::date + 30")
printf 'Tallinn screening date: %s\n' "$screening_day"
run_psql -c 'SELECT version();'

benchmark() {
    MSYS2_ARG_CONV_EXCL='--file=' docker exec "$container" pgbench \
        -U postgres -d kino_pgbench --no-vacuum \
        --client=8 --jobs=2 --time="$1" --define="screening_day='$screening_day'" \
        --file=/tmp/pgbench-screenings.sql
}

for run in 1 2 3; do
    for variant in indexed unindexed; do
        if [[ $variant == indexed ]]; then
            run_psql -qc 'CREATE INDEX IF NOT EXISTS screenings_starts_at_index ON screenings (starts_at);'
        else
            run_psql -qc 'DROP INDEX screenings_starts_at_index;'
        fi

        printf '\nRun %s of 3, %s\n' "$run" "$variant"
        if [[ $run == 1 ]]; then
            { printf 'EXPLAIN (ANALYZE, BUFFERS)\n'; cat bench/pgbench-screenings.sql; } \
                | run_psql -v "screening_day='$screening_day'"
        fi

        benchmark 3 >/dev/null
        benchmark 10
    done
done
