import { spawnSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';
import { readFileSync, writeFileSync } from 'node:fs';
import { cpus, totalmem, platform, release } from 'node:os';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '../..');
const compose = ['compose', '-f', 'tests/load/compose.yml'];
const duration = process.env.DURATION || '30s';
const resultsDirectory = resolve(root, 'docs/testing/results/k6');
const rows = [];

function capture(command, args) {
  const result = spawnSync(command, args, { cwd: root, encoding: 'utf8' });
  if (result.status !== 0) throw new Error(result.stderr || `${command} failed.`);
  return result.stdout.trim();
}

const environment = {
  started_at: new Date().toISOString(),
  commit: capture('git', ['rev-parse', 'HEAD']),
  application_tree: capture('git', ['rev-parse', 'HEAD:backend']),
  host: { os: platform(), release: release(), cpu: cpus()[0].model, logical_cpus: cpus().length, memory_bytes: totalmem() },
  docker: JSON.parse(capture('docker', ['info', '--format',
    '{"version":"{{.ServerVersion}}","cpus":{{.NCPU}},"memory_bytes":{{.MemTotal}},"architecture":"{{.Architecture}}"}'])),
  images: Object.fromEntries(['php:8.4-cli', 'postgres:17', 'grafana/k6:2.4.0'].map((name) => [
    name, JSON.parse(capture('docker', ['image', 'inspect', name, '--format', '{{json .RepoDigests}}'])),
  ])),
};

function docker(args, env, allowThresholdFailure = false) {
  const result = spawnSync('docker', [...compose, ...args], {
    cwd: root,
    env: { ...process.env, ...env },
    stdio: 'inherit',
  });
  if (result.error) throw result.error;
  if (result.status !== 0 && !(allowThresholdFailure && result.status === 99)) {
    throw new Error(`Docker exited with code ${result.status}.`);
  }
  return result.status;
}

for (const workers of [1, 4]) {
  const env = { LOAD_WORKERS: String(workers) };
  docker(['up', '--build', '-d', '--wait', 'load-api'], env);
  docker(['run', '--rm', 'load-seed'], env);

  for (const vus of [1, 10, 30]) {
    const runId = `workers${workers}-vus${vus}`;
    const exitCode = docker([
      'run', '--no-deps', '--rm',
      '-e', `VUS=${vus}`, '-e', `DURATION=${duration}`, '-e', `RUN_ID=${runId}`,
      'k6',
    ], env, true);
    const report = JSON.parse(readFileSync(resolve(resultsDirectory, `${runId}.json`), 'utf8'));
    if (!report.results.requests || report.results.mean_ms === null || report.results.p95_ms === null) {
      throw new Error(`${runId} has no usable measurements.`);
    }
    rows.push({ run_id: runId, exit_code: exitCode, ...report.configuration, ...report.results });
  }
}

environment.php = capture('docker', [...compose, 'exec', '-T', 'load-api', 'php', '-r', 'echo PHP_VERSION;']);
environment.laravel = capture('docker', [...compose, 'exec', '-T', 'load-api', 'php', 'artisan', '--version']);
environment.postgresql = capture('docker', [...compose, 'exec', '-T', 'load-database', 'psql', '-U', 'kino_load', '-d', 'kino_load', '-Atc', 'SHOW server_version']);
environment.k6 = capture('docker', [...compose, 'run', '--no-deps', '--rm', 'k6', 'version']);
environment.completed_at = new Date().toISOString();
writeFileSync(resolve(resultsDirectory, 'environment.json'), JSON.stringify(environment, null, 2) + '\n');
writeFileSync(resolve(resultsDirectory, 'suite.json'), JSON.stringify(rows, null, 2) + '\n');
console.table(rows.map(({ run_id, requests, requests_per_second, mean_ms, p95_ms, error_rate, exit_code }) => ({
  run_id, requests, rps: requests_per_second.toFixed(2), mean_ms: mean_ms.toFixed(2),
  p95_ms: p95_ms.toFixed(2), error_rate, exit_code,
})));
console.log('Exit code 99 means a k6 threshold failed. See each result JSON for details.');
