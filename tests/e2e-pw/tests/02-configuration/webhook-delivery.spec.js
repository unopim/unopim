const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

// Real end-to-end proof that product updates reach an external webhook.
// Uses webhook.site, which provides a public inbox per token and a REST API
// to list received requests. The earlier bug: bulk edit + mass status never
// POSTed, because the queued BulkProductUpdate job skipped the event dispatch
// and ProductComparer dropped status diffs when the old value was 0.
//
// This spec exercises the bulk-edit path end-to-end. The single edit, mass
// status and REST API paths are covered by deterministic Pest feature tests
// (ProductBulkEditTest, ProductTest, ApiProductTest) which don't depend on
// webhook.site availability.
//
// Set `SKIP_WEBHOOK_SITE=1` to skip the whole describe block in offline runs.

const WEBHOOK_SITE = 'https://webhook.site';
const WAIT_MS = 20000;
const POLL_MS = 500;

async function createInbox(request) {
  const response = await request.post(`${WEBHOOK_SITE}/token`, {
    headers: { Accept: 'application/json' },
  });

  if (!response.ok()) {
    throw new Error(`Failed to create webhook.site inbox: ${response.status()}`);
  }

  return (await response.json()).uuid;
}

async function deleteInbox(request, uuid) {
  if (!uuid) return;
  await request.delete(`${WEBHOOK_SITE}/token/${uuid}`).catch(() => {});
}

async function waitForInboundRequest(request, uuid, matcher = () => true) {
  const deadline = Date.now() + WAIT_MS;
  while (Date.now() < deadline) {
    const r = await request.get(`${WEBHOOK_SITE}/token/${uuid}/requests?sorting=newest`, {
      headers: { Accept: 'application/json' },
    });
    if (r.ok()) {
      const body = await r.json();
      const hits = Array.isArray(body?.data) ? body.data : [];
      const hit = hits.find(matcher);
      if (hit) return hit;
    }
    await new Promise((res) => setTimeout(res, POLL_MS));
  }
  throw new Error(`webhook.site inbox ${uuid} got no matching request within ${WAIT_MS}ms`);
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
  test.skip(
    process.env.SKIP_WEBHOOK_SITE === '1',
    'webhook.site integration disabled via SKIP_WEBHOOK_SITE=1',
  );

  let inboxUuid;

  test.afterEach(async ({ adminPage, playwright }) => {
    // Leave the system in the same state the test found it: disable the
    // webhook toggle and wipe any log rows this run created, so other
    // specs that assume an empty log / disabled webhook still pass.
    await disableWebhook(adminPage).catch(() => {});
    await clearWebhookLogs(adminPage).catch(() => {});

    if (inboxUuid) {
      const apiRequest = await playwright.request.newContext();
      await deleteInbox(apiRequest, inboxUuid);
      await apiRequest.dispose();
      inboxUuid = undefined;
    }
  });

  test('bulk edit save dispatches a webhook POST for each updated product', async ({ adminPage, playwright }) => {
    const apiRequest = await playwright.request.newContext();

    try {
      inboxUuid = await createInbox(apiRequest);
    } catch (err) {
      test.skip(true, `webhook.site unavailable: ${err.message}`);
      return;
    }

    const webhookUrl = `${WEBHOOK_SITE}/${inboxUuid}`;
    await configureWebhook(adminPage, webhookUrl);

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

    const hit = await waitForInboundRequest(apiRequest, inboxUuid, (req) => req.method === 'POST');
    expect(hit).toBeTruthy();
    expect(hit.method).toBe('POST');

    await apiRequest.dispose();
  });
});
