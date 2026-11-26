#!/bin/sh
set -eu

psql -X -v ON_ERROR_STOP=1 -f /bench/seed.sql
psql -X -A -t -v ON_ERROR_STOP=1 -f /bench/manifest.sql > /results/fixture.json
cat /results/fixture.json
