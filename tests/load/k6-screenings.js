import http from 'k6/http';
import { check, sleep } from 'k6';
import { Counter, Rate, Trend } from 'k6/metrics';

const fixture = JSON.parse(open('/results/fixture.json'));
const baseUrl = 'http://load-api:8000';
const vus = Number(__ENV.VUS || 10);
const duration = __ENV.DURATION || '30s';
const runId = __ENV.RUN_ID || 'latest';
const pauseSeconds = 0.2;
const perPage = 30;

const requestCount = new Counter('screening_requests');
const errorRate = new Rate('screening_errors');
const latency = new Trend('screening_latency', true);
const setupDuration = new Trend('setup_duration_ms', true);

const queries = [
  { view: 'day', suffix: '', total: fixture.totals.day },
  { view: 'cinema', suffix: `&cinema_id=${fixture.cinema_id}`, total: fixture.totals.cinema },
  { view: 'movie', suffix: `&movie_id=${fixture.movie_id}`, total: fixture.totals.movie },
];

export const options = {
  scenarios: {
    screenings: { executor: 'constant-vus', vus, duration, gracefulStop: '15s' },
  },
  summaryTrendStats: ['avg', 'min', 'med', 'max', 'p(90)', 'p(95)', 'p(99)'],
  thresholds: {
    screening_errors: ['rate==0'],
    checks: ['rate==1'],
    screening_latency: ['p(95)<3000'],
    'screening_latency{view:day}': ['p(95)<3000'],
    'screening_latency{view:cinema}': ['p(95)<3000'],
    'screening_latency{view:movie}': ['p(95)<3000'],
  },
};

function requestSchedule(query) {
  return http.get(`${baseUrl}/api/screenings?date=${fixture.date}&per_page=${perPage}${query.suffix}`, {
    headers: { Accept: 'application/json' },
    redirects: 0,
    timeout: '10s',
    tags: { name: `screenings/${query.view}`, view: query.view },
  });
}

function validSchedule(response, query) {
  if (response.status !== 200) return false;

  let body;
  try {
    body = response.json();
  } catch {
    return false;
  }

  if (!body || !body.meta || !Array.isArray(body.data)) return false;
  if (body.meta.total !== query.total || body.data.length !== Math.min(perPage, query.total)) return false;

  const start = Date.parse(fixture.starts_at);
  const end = Date.parse(fixture.ends_at);
  return body.data.every((screening) => {
    const time = Date.parse(screening.starts_at);
    if (!(time >= start && time < end) || !screening.movie || !screening.hall) return false;
    if (query.view === 'cinema' && screening.hall.cinema?.id !== fixture.cinema_id) return false;
    if (query.view === 'movie' && screening.movie.id !== fixture.movie_id) return false;
    return true;
  });
}

export function setup() {
  const startedAt = Date.now();
  if (fixture.rows.screenings !== 10000 || fixture.rows.tickets !== 50000) {
    throw new Error('Expected 10,000 screenings and 50,000 tickets. Rerun the load-seed service.');
  }
  if (fixture.totals.day !== 500 || fixture.totals.cinema !== 25 || fixture.totals.movie !== 5) {
    throw new Error('Expected non-empty day, cinema and movie fixtures. Rerun the load-seed service.');
  }
  if (Date.now() >= Date.parse(fixture.starts_at)) {
    throw new Error('The fixture date is stale. Rerun the load-seed service.');
  }

  // These checks also warm each query before measurements. Custom metrics
  // below count only default-function requests, excluding this setup phase.
  for (let round = 0; round < 5; round += 1) {
    for (const query of queries) {
      if (!validSchedule(requestSchedule(query), query)) {
        throw new Error(`Warm-up returned an incorrect ${query.view} schedule.`);
      }
    }
  }
  setupDuration.add(Date.now() - startedAt);
}

export default function () {
  const query = queries[(__ITER + __VU) % queries.length];
  const response = requestSchedule(query);
  const valid = validSchedule(response, query);
  const tags = { view: query.view };

  requestCount.add(1, tags);
  latency.add(response.timings.duration, tags);
  errorRate.add(!valid, tags);
  check(response, {
    'HTTP status is 200': (result) => result.status === 200,
    'schedule rows and filters are correct': () => valid,
  }, tags);

  sleep(pauseSeconds);
}

export function handleSummary(data) {
  const timings = data.metrics.screening_latency?.values || {};
  const requests = data.metrics.screening_requests?.values || {};
  const errors = data.metrics.screening_errors?.values || {};
  const setupMs = data.metrics.setup_duration_ms?.values.avg || 0;
  const measurementSeconds = (data.state.testRunDurationMs - setupMs) / 1000;
  const report = {
    run_id: runId,
    completed_at: new Date().toISOString(),
    configuration: {
      vus,
      duration,
      pause_seconds: pauseSeconds,
      php_cli_server_workers_setting: Number(__ENV.WORKERS || 4),
      per_page: perPage,
    },
    fixture,
    results: {
      requests: requests.count || 0,
      measurement_seconds: measurementSeconds,
      requests_per_second: measurementSeconds > 0 ? (requests.count || 0) / measurementSeconds : 0,
      mean_ms: timings.avg ?? null,
      p95_ms: timings['p(95)'] ?? null,
      error_rate: errors.rate ?? null,
    },
    metrics: data.metrics,
    state: data.state,
  };

  return {
    [`/results/${runId}.json`]: JSON.stringify(report, null, 2),
    stdout: `${runId}: ${report.results.requests} measured requests, `
      + `mean=${report.results.mean_ms?.toFixed(2)} ms, p95=${report.results.p95_ms?.toFixed(2)} ms, `
      + `error rate=${report.results.error_rate}\n`,
  };
}
