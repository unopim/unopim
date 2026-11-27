const http = require('http');
const { test, expect } = require('../../utils/fixtures');
const { navigateTo, gotoPage, clickSave } = require('../../utils/helpers');
const { xsrfToken } = require('../../utils/category-field-helpers');
const { ensureAgenticPimEnabled } = require('../../utils/magic-ai-helpers');
const { fillRequiredProductFields } = require('../../utils/gallery-helpers');
const { deleteProductBySku } = require('../../utils/product-helpers');

// Search Products must show a variant group and its child products under the Name they
// inherit from the configurable parent, not "(unnamed)" or their own url_key/SKU.
// A local Groq-compatible LLM calls search_products and echoes each sku=name pair.
// Needs WEBHOOK_ALLOW_LOOPBACK=true on the app so the platform may target 127.0.0.1.

const JSON_HEADERS = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };
const STRUCTURE_CODE = 'e2e_color_size';

function sse(res, chunks) {
  res.writeHead(200, { 'Content-Type': 'text/event-stream' });

  for (const chunk of chunks) {
    res.write(`data: ${JSON.stringify({ id: 'mock', model: 'mock-model', choices: [{ index: 0, ...chunk }] })}\n\n`);
  }

  res.end('data: [DONE]\n\n');
}

function replyFor(body, query) {
  const toolMessage = [...(body.messages ?? [])].reverse().find((message) => message.role === 'tool');

  if (! toolMessage) {
    const call = { index: 0, id: 'call_1', type: 'function', function: { name: 'search_products', arguments: JSON.stringify({ query }) } };

    return {
      stream: [{ delta: { role: 'assistant', tool_calls: [call] } }, { delta: {}, finish_reason: 'tool_calls' }],
      message: { role: 'assistant', content: null, tool_calls: [{ ...call, index: undefined }] },
      finish: 'tool_calls',
    };
  }

  const result = JSON.parse(toolMessage.content);
  const text = result.error
    ? `ERROR: ${result.error}`
    : `FOUND: ${(result.products ?? []).map((product) => `${product.sku}=${product.name}`).join('; ')}`;

  return {
    stream: [{ delta: { role: 'assistant', content: text } }, { delta: {}, finish_reason: 'stop' }],
    message: { role: 'assistant', content: text },
    finish: 'stop',
  };
}

function startMockLlm(query) {
  return new Promise((resolve, reject) => {
    const server = http.createServer((req, res) => {
      let raw = '';
      req.on('data', (chunk) => { raw += chunk; });
      req.on('end', () => {
        const body = raw ? JSON.parse(raw) : {};
        const reply = replyFor(body, query);

        if (body.stream) {
          return sse(res, reply.stream);
        }

        res.writeHead(200, { 'Content-Type': 'application/json' });
        res.end(JSON.stringify({
          id: 'mock',
          model: 'mock-model',
          choices: [{ index: 0, message: reply.message, finish_reason: reply.finish }],
          usage: { prompt_tokens: 1, completion_tokens: 1, total_tokens: 2 },
        }));
      });
    });

    server.listen(0, '0.0.0.0', () => resolve({
      url: `http://127.0.0.1:${server.address().port}`,
      close: () => new Promise((done) => server.close(done)),
    }));
    server.on('error', reject);
  });
}

async function postJson(page, url, data, method = 'post') {
  return page.request[method](url, {
    headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': await xsrfToken(page.context()) },
    data,
  });
}

/**
 * Create a configurable product on a color → size structure of the default family,
 * name it, then add one variant group and one child under it.
 */
async function createTwoLevelProduct(page, sku, name) {
  await page.goto('/admin/catalog/products', { waitUntil: 'load' });

  await postJson(page, '/admin/catalog/attribute-families/edit/1/variant-structures', {
    structure: { code: STRUCTURE_CODE, name: 'E2E Color Size', levels: 2, axes: { level_1: ['color'], level_2: ['size'] }, placements: {} },
  }, 'put');

  const offered = await postJson(page, '/admin/catalog/products/create', { type: 'configurable', attribute_family_id: 1, sku });
  const structureId = ((await offered.json()).data?.variant_structures ?? []).find((s) => s.code === STRUCTURE_CODE)?.id;

  expect(structureId, 'two-level variant structure is offered').toBeTruthy();

  const created = await postJson(page, '/admin/catalog/products/create', { type: 'configurable', attribute_family_id: 1, sku, variant_structure_id: structureId });
  const createdBody = await created.json();
  const parentId = String(createdBody.data?.redirect_url ?? createdBody.redirect_url ?? '').match(/products\/edit\/(\d+)/)?.[1];

  expect(parentId, JSON.stringify(createdBody)).toBeTruthy();

  await gotoPage(page, `/admin/catalog/products/edit/${parentId}`);
  await fillRequiredProductFields(page, name);
  await clickSave(page, 'Save Product');
  await expect(page.locator('#app').getByText(/Product updated successfully/i)).toBeVisible({ timeout: 20000 });

  const group = await postJson(page, `/admin/catalog/products/${parentId}/variant-node`, { role: 'variant_group', sku: `${sku}-RED`, values: { color: 'Red' } });
  expect(group.ok(), await group.text()).toBeTruthy();

  const groupId = (await group.json()).data.id;
  const child = await postJson(page, `/admin/catalog/products/${parentId}/variant-node`, { role: 'simple', parent_id: groupId, sku: `${sku}-RED-S`, values: { size: 'S' } });
  expect(child.ok(), await child.text()).toBeTruthy();
}

test.describe('Agenting PIM - Search Products inherited names', { tag: '@global-state' }, () => {
  test('shows the parent name for variant groups and child products', async ({ adminPageWithWidget: page }) => {
    test.setTimeout(180000);

    const stamp = Date.now();
    const sku = `E2E-INHERIT-${stamp}`;
    const name = `Inherited Tee ${stamp}`;
    const label = `E2E Mock LLM ${stamp}`;
    const llm = await startMockLlm(sku);
    let platformId = null;

    try {
      await ensureAgenticPimEnabled(page);
      await createTwoLevelProduct(page, sku, name);

      const created = await postJson(page, '/admin/magic-ai/platforms', {
        label, provider: 'groq', api_url: llm.url, api_key: 'mock-key', models: 'mock-model', is_default: false, status: true,
      });
      expect(created.ok(), await created.text()).toBeTruthy();

      const grid = await page.request.get('/admin/magic-ai/platforms', { headers: JSON_HEADERS });
      platformId = ((await grid.json()).records ?? []).find((row) => row.label === label)?.id ?? null;

      await navigateTo(page, 'dashboard');
      await page.getByRole('button', { name: 'Open Agenting PIM' }).click();
      await page.getByRole('button', { name: 'Search Products' }).click();
      await page.locator('select[title="Select AI Platform"]').selectOption({ label: `${label} (Groq)` });
      await page.locator('#agenting-pim-panel textarea').first().fill(`Search products ${sku}`);
      await page.getByRole('button', { name: 'Send' }).click();

      const panel = page.locator('#agenting-pim-panel');

      await expect(panel).toContainText('FOUND:', { timeout: 60000 });
      await expect(panel).toContainText(`${sku}-RED=${name}`);
      await expect(panel).toContainText(`${sku}-RED-S=${name}`);
      await expect(panel).not.toContainText('(unnamed)');
    } finally {
      if (platformId) {
        await postJson(page, `/admin/magic-ai/platforms/${platformId}`, {}, 'delete');
      }

      await deleteProductBySku(page, `${sku}-RED-S`);
      await deleteProductBySku(page, `${sku}-RED`);
      await deleteProductBySku(page, sku);
      await llm.close();
    }
  });
});
