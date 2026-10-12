const fs = require('fs');
const path = require('path');
const { test } = require('@playwright/test');

const ROUTES = require('./route-perf-urls.json');

const SLOW_MS = Number(process.env.SLOW_MS || 1000);
const OUT = path.resolve(__dirname, '../../.route-perf.json');

test('sweeps every admin GET route and records server response time', async ({ request }) => {
  test.setTimeout(30 * 60 * 1000);

  const results = [];

  for (const route of ROUTES) {
    const started = Date.now();
    let status = 0;
    let bytes = 0;

    try {
      const response = await request.get(route.url, { timeout: 120_000, maxRedirects: 0 });
      status = response.status();
      bytes = (await response.body()).length;
    } catch (error) {
      status = -1;
      route.error = String(error).split('\n')[0];
    }

    results.push({ ...route, status, ms: Date.now() - started, bytes });
  }

  results.sort((a, b) => b.ms - a.ms);

  fs.writeFileSync(OUT, JSON.stringify(results, null, 2));

  const slow = results.filter((r) => r.ms >= SLOW_MS);
  const failed = results.filter((r) => r.status >= 500 || r.status < 0);

  console.log(`\nswept ${results.length} routes — ${slow.length} slower than ${SLOW_MS}ms, ${failed.length} erroring\n`);
  console.log(['ms'.padStart(7), 'status'.padStart(6), 'kb'.padStart(7), 'url'].join('  '));

  for (const r of results.slice(0, 25)) {
    console.log([
      String(r.ms).padStart(7),
      String(r.status).padStart(6),
      String(Math.round(r.bytes / 1024)).padStart(7),
      r.url,
    ].join('  '));
  }

  if (failed.length) {
    console.log('\nerroring routes:');
    for (const r of failed) console.log(`  ${r.status}  ${r.url}`);
  }
});
