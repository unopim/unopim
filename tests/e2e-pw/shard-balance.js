// shard-balance.js — split the spec files into CI shards of similar duration.
//
// Playwright's --shard hands every shard the same number of tests, in file-path
// order. Our slow UI specs and fast API specs sit in different directories, so
// equal counts gave shards from about 1 to 4.5 minutes. Instead, each shard
// takes a set of whole files picked by their recorded duration and by how they
// would spread over the shard's workers.
//
// shard-timings.json holds seconds per spec file from a full CI run. A file it
// does not list counts as the median, so a new spec still lands in a shard.
// Refresh the timings when the shards drift apart again.
//
// A spec tagged @global-state changes store-wide settings that other specs
// read. Each shard runs those files in a second, single-worker pass (PW_PASS=
// serial) after everything else (PW_PASS=parallel), so they never overlap with
// anything. The whole file moves, because its other tests may rely on what the
// tagged ones set.

const fs = require('fs');
const path = require('path');

const TEST_DIR = path.join(__dirname, 'tests');
const TIMINGS = path.join(__dirname, 'shard-timings.json');

/** Every spec file Playwright would collect, relative to the test directory. */
function specFiles(dir = TEST_DIR) {
  return fs.readdirSync(dir, { withFileTypes: true }).flatMap((entry) => {
    const full = path.join(dir, entry.name);

    if (entry.isDirectory()) {
      return entry.name === '99-debug' ? [] : specFiles(full);
    }

    return entry.name.endsWith('.spec.js') ? [path.relative(TEST_DIR, full).split(path.sep).join('/')] : [];
  });
}

function source(file) {
  return fs.readFileSync(path.join(TEST_DIR, file), 'utf8');
}

/** Whether a spec opts its tests into parallel mode, so they spread over workers. */
function runsInParallel(file) {
  return /test\.describe\.configure\(\{\s*mode:\s*'parallel'/.test(source(file));
}

/** Whether a spec changes store-wide state and must run in the serial pass. */
function changesGlobalState(file) {
  return source(file).includes('@global-state');
}

/**
 * Estimate a shard's duration: Playwright hands files to free workers in path
 * order, so a long file that starts late stretches the shard.
 */
function estimate(units, workers) {
  const free = Array(workers).fill(0);

  for (const unit of [...units].sort((a, b) => a.file.localeCompare(b.file))) {
    const earliest = free.indexOf(Math.min(...free));

    free[earliest] += unit.seconds;
  }

  return Math.max(...free);
}

/**
 * Assign files longest-first, each to the shard that would finish earliest.
 * @param {number} total
 * @param {number} workers
 * @returns {string[][]}
 */
function plan(total, workers = 1) {
  const timings = JSON.parse(fs.readFileSync(TIMINGS, 'utf8'));
  const known = Object.values(timings).sort((a, b) => a - b);
  const median = known.length ? known[Math.floor(known.length / 2)] : 1;

  const files = specFiles()
    .map((file) => ({ file, seconds: timings[file] ?? median }))
    .sort((a, b) => b.seconds - a.seconds || a.file.localeCompare(b.file));

  const shards = Array.from({ length: total }, () => ({ units: [], serial: 0, files: [] }));
  const duration = (shard, units, serial) => estimate([...shard.units, ...units], workers) + shard.serial + serial;

  for (const { file, seconds } of files) {
    const serial = changesGlobalState(file) ? seconds : 0;
    const units = serial ? [] : runsInParallel(file)
      ? Array.from({ length: workers }, (_, i) => ({ file: `${file}#${i}`, seconds: seconds / workers }))
      : [{ file, seconds }];

    const target = shards
      .map((shard) => ({ shard, after: duration(shard, units, serial) }))
      .reduce((best, candidate) => (candidate.after < best.after ? candidate : best)).shard;

    target.units.push(...units);
    target.serial += serial;
    target.files.push(file);
  }

  return shards.map((shard) => shard.files.sort());
}

/**
 * The absolute paths of the files for one shard, given as "current/total",
 * limited to one pass ("parallel" or "serial") when given.
 * @param {string} spec
 * @param {number} workers
 * @param {string} [pass]
 * @returns {string[]}
 */
function filesForShard(spec, workers = 1, pass = undefined) {
  const [current, total] = spec.split('/').map(Number);

  if (! (total >= 1 && current >= 1 && current <= total)) {
    throw new Error(`PW_SHARD must look like "2/8", got "${spec}"`);
  }

  if (pass !== undefined && pass !== 'parallel' && pass !== 'serial') {
    throw new Error(`PW_PASS must be "parallel" or "serial", got "${pass}"`);
  }

  return plan(total, workers)[current - 1]
    .filter((file) => pass === undefined || changesGlobalState(file) === (pass === 'serial'))
    .map((file) => path.join(TEST_DIR, file));
}

module.exports = { plan, filesForShard };
