const http = require('http');
const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

// Real end-to-end proof that product updates reach an external webhook.
// Uses a local HTTP server spun up inside the test process to capture
// incoming webhook POSTs — no external service dependency.
//
// The earlier bug: bulk edit + mass status never POSTed, because the queued
// BulkProductUpdate job skipped the event dispatch and ProductComparer
// dropped status diffs when the old value was 0.
//
// This spec exercises the bulk-edit path end-to-end. The single edit, mass
// status and REST API paths are covered by deterministic Pest feature tests
// (ProductBulkEditTest, ProductTest, ApiProductTest).

const WAIT_MS = 20000;
const POLL_MS = 300;

/**
 * Spin up a lightweight HTTP server that collects every incoming request.
 * Returns { url, requests, close }.
 */
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

    // Listen on a random available port on all interfaces so the Laravel
    // app (running on 127.0.0.1:8000) can reach it.
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

/**
 * Poll the local requests array until a match is found or timeout.
 */
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

async function configureWebhook(adminPage, url) {
  await navigateTo(adminPage, 'webhook');
  await adminPage.locator('input[name="webhook_url"]').fill(url);

  const toggle = adminPage.locator('label[for="webhook_active"]');
  const checkbox = adminPage.locator('input[name="webhook_active"]');
  const isChecked = await checkbox.isChecked().catch(() => false);
  if (!isChecked) await toggle.click();

  await adminPage.getByRole('button', { name: 'Save' }).click();
  await expect(
    adminPage.locator('#app').getByText('Webhook settings saved successfully'),
  ).toBeVisible();
}

async function disableWebhook(adminPage) {
  await navigateTo(adminPage, 'webhook');
  const checkbox = adminPage.locator('input[name="webhook_active"]');
  const isChecked = await checkbox.isChecked().catch(() => false);
  if (isChecked) {
    await adminPage.locator('label[for="webhook_active"]').click();
    await adminPage.getByRole('button', { name: 'Save' }).click();
    await expect(
      adminPage.locator('#app').getByText('Webhook settings saved successfully'),
    ).toBeVisible();
  }
}

// Wipe every row in the webhook log datagrid. Other specs (e.g.
// webhook.spec.js:101) assert "No Records Available" on the log page, so
// this spec must leave the log empty.
async function clearWebhookLogs(adminPage) {
  const result = await adminPage.evaluate(async () => {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

    const listing = await fetch('/admin/webhook/logs', {
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

    const del = await fetch('/admin/webhook/logs/mass-delete', {
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
  // Navigate first so the browser context owns the admin session, then
  // call the datagrid AJAX endpoint from inside the page (cookies, CSRF,
  // and XHR detection are all handled natively by fetch in-page).
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
  // DataGrid records use `product_id` for the PK; fall back to other
  // common names in case the grid evolves.
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
    // Leave the system in the same state the test found it: disable the
    // webhook toggle and wipe any log rows this run created, so other
    // specs that assume an empty log / disabled webhook still pass.
    await disableWebhook(adminPage).catch(() => {});
    await clearWebhookLogs(adminPage).catch(() => {});

    if (webhookServer) {
      await webhookServer.close().catch(() => {});
      webhookServer = undefined;
    }
  });

  test('bulk edit save dispatches a webhook POST for each updated product', async ({ adminPage }) => {
    webhookServer = await createLocalWebhookServer();

    await configureWebhook(adminPage, webhookServer.url);

    const productId = await getFirstProductId(adminPage);

    const token = await getCsrfToken(adminPage.context());
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
            [productId]: {},
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
