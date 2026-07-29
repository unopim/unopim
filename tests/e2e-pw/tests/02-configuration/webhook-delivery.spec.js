const http = require('http');
const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo } = require('../../utils/helpers');

// E2E proof that bulk-edit product updates reach an external webhook via a local capture server.
// Regression: queued BulkProductUpdate skipped event dispatch; ProductComparer dropped status diffs when old=0.

const WAIT_MS = 20000;
const POLL_MS = 300;

// Local HTTP server that records every incoming request. Returns { url, requests, close }.
function createLocalWebhookServer() {
  const requests = [];

  return new Promise((resolve, reject) => {
    const server = http.createServer((req, res) => {
      let body = '';
      req.on('data', (chunk) => { body += chunk; });
      req.on('end', () => {
        requests.push({
          method:  req.method,
          url:     req.url,
          headers: { ...req.headers },
          body,
        });
        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({ ok: true }));
      });
    });

    // Random port on all interfaces so the app on 127.0.0.1 can reach it.
    server.listen(0, '0.0.0.0', () => {
      const port = server.address().port;
      resolve({
        url:      `http://127.0.0.1:${port}`,
        requests,
        close:    () => new Promise((res) => server.close(res)),
      });
    });

    server.on('error', reject);
  });
}

async function waitForRequest(requests, matcher = () => true) {
  const deadline = Date.now() + WAIT_MS;
  while (Date.now() < deadline) {
    const hit = requests.find(matcher);
    if (hit) return hit;
    await new Promise((res) => setTimeout(res, POLL_MS));
  }
  throw new Error(`Local webhook server got no matching request within ${WAIT_MS}ms`);
}

async function getCsrfToken(context) {
  const cookies = await context.cookies();
  const xsrf = cookies.find((c) => c.name === 'XSRF-TOKEN');
  if (!xsrf) throw new Error('XSRF-TOKEN cookie missing — admin session not seeded');
  return decodeURIComponent(xsrf.value);
}

// Create an active webhook subscribed to product.updated via the index create modal.
async function configureWebhook(adminPage, url) {
  await navigateTo(adminPage, 'webhook');
  await adminPage.getByRole('button', { name: 'Create Webhook' }).click();
  await adminPage.locator('input[name="name"]').waitFor({ state: 'visible', timeout: 15000 });
  await adminPage.locator('input[name="name"]').fill(`E2E Delivery ${Date.now()}`);
  await adminPage.locator('input[name="url"]').fill(url);

  const events = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="events"]') });
  await events.locator('.multiselect__tags').click();
  await events.locator('.multiselect__option', { hasText: 'Product Updated' }).first().click();

  await Promise.all([
    adminPage.waitForURL(/\/webhook\/edit\/\d+/, { timeout: 20000 }).catch(() => {}),
    adminPage.locator('form[ref="webhookCreateForm"], .modal').getByRole('button', { name: 'Save' }).last().click(),
  ]);
}

// Remove every webhook so each run starts clean (afterEach cleanup).
async function disableWebhook(adminPage) {
  await navigateTo(adminPage, 'webhook');
  for (let i = 0; i < 10; i++) {
    const del = adminPage.locator('span[title="Delete"]').first();
    if (!(await del.isVisible({ timeout: 3000 }).catch(() => false))) {
      break;
    }
    await del.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForTimeout(800);
  }
}

// Wipe webhook log rows; other specs assert "No Records Available" so leave the log empty.
async function clearWebhookLogs(adminPage) {
  const result = await adminPage.evaluate(async () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const listing = await fetch('/admin/configuration/webhook/logs', {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept:             'application/json',
      },
    });
    if (!listing.ok) return { ok: false, step: 'listing', status: listing.status };
    const body = await listing.json();
    const ids = (Array.isArray(body?.records) ? body.records : [])
      .map((r) => r.id ?? r.log_id ?? r.record_id)
      .filter(Boolean);

    if (ids.length === 0) return { ok: true, deleted: 0 };

    const del = await fetch('/admin/configuration/webhook/logs/mass-delete', {
      method:      'POST',
      credentials: 'same-origin',
      headers: {
        'Content-Type':     'application/json',
        'X-CSRF-TOKEN':     csrf,
        'X-Requested-With': 'XMLHttpRequest',
        Accept:             'application/json',
      },
      body: JSON.stringify({ indices: ids }),
    });
    return { ok: del.ok, step: 'delete', status: del.status, deleted: ids.length };
  });

  if (!result.ok) {
    throw new Error(`Webhook log cleanup failed at ${result.step} (status ${result.status})`);
  }
}

async function getFirstProductId(adminPage) {
  // Navigate first so the page owns the session; in-page fetch then carries cookies/CSRF/XHR natively.
  await adminPage.goto('/admin/catalog/products', { waitUntil: 'domcontentloaded' });

  const json = await adminPage.evaluate(async () => {
    const r = await fetch('/admin/catalog/products', {
      credentials: 'same-origin',
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
        Accept:             'application/json',
      },
    });
    if (!r.ok) return { __error: `status ${r.status}` };
    try {
      return await r.json();
    } catch (e) {
      return { __error: `not json: ${e.message}` };
    }
  });

  if (json?.__error) {
    throw new Error(`Datagrid fetch failed: ${json.__error}`);
  }

  const record = Array.isArray(json?.records) ? json.records[0] : null;
  // DataGrid PK is product_id; fall back to other names if the grid evolves.
  const id = record?.product_id ?? record?.id ?? record?.record_id;
  if (!id) {
    throw new Error(
      `DataGrid record missing product id. available keys=${record ? Object.keys(record).join(',') : 'n/a'}`,
    );
  }
  return id;
}

test.describe('Product webhook delivery — bulk edit E2E', () => {
  let webhookServer;

  test.afterEach(async ({ adminPage }) => {
    // Restore state: disable webhook + wipe log rows so specs expecting empty log/disabled webhook pass.
    await disableWebhook(adminPage).catch(() => {});
    await clearWebhookLogs(adminPage).catch(() => {});

    if (webhookServer) {
      await webhookServer.close().catch(() => {});
      webhookServer = undefined;
    }
  });

  test('bulk edit save dispatches a webhook POST for each updated product', async ({ adminPage }) => {
    // SafeWebhookUrl only allows loopback; app must run same-host (serve), not Docker :8024 which can't reach host 127.0.0.1.
    const appUrl = process.env.BASE_URL || 'http://127.0.0.1:8000';
    test.skip(
      ! /\/\/(127\.0\.0\.1|localhost)[:/]/.test(appUrl),
      'Requires the app on the test-host loopback (same-host serve) with WEBHOOK_ALLOW_LOOPBACK=true.',
    );

    webhookServer = await createLocalWebhookServer();

    await configureWebhook(adminPage, webhookServer.url);

    const productId = await getFirstProductId(adminPage);

    const token = await getCsrfToken(adminPage.context());

    // Webhook fires only on a real audit diff; rotate SKU to a unique value to guarantee an UPDATE and delivery.
    const newSku = `webhook-test-${Date.now()}`;
    const saveResponse = await adminPage.request.post(
      '/admin/catalog/products/bulkedit/save',
      {
        headers: {
          'X-XSRF-TOKEN':     token,
          'X-Requested-With': 'XMLHttpRequest',
          Accept:             'application/json',
        },
        data: {
          data: {
            [productId]: { sku: newSku },
          },
        },
      },
    );
    expect(saveResponse.ok()).toBeTruthy();

    const hit = await waitForRequest(webhookServer.requests, (req) => req.method === 'POST');
    expect(hit).toBeTruthy();
    expect(hit.method).toBe('POST');
  });
});
