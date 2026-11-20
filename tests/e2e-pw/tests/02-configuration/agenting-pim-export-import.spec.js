const fs = require('fs');
const http = require('http');
const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');
const { xsrfToken } = require('../../utils/category-field-helpers');
const { ensureAgenticPimEnabled } = require('../../utils/magic-ai-helpers');
const { createSimpleProductRecord, deleteProductBySku } = require('../../utils/product-helpers');

// The Export Products and Bulk Import CSV capabilities must hand back a usable result:
// the export file is downloadable from the chat and the job tracker, and a product CSV in
// UnoPim's own export format (per-currency "price (USD)" columns) lands in the catalog.
// A local Groq-compatible LLM calls the tool and echoes its result.
// Needs WEBHOOK_ALLOW_LOOPBACK=true on the app so the platform may target 127.0.0.1,
// and a queue worker (or QUEUE_CONNECTION=sync) so the queued import runs.

const JSON_HEADERS = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

function sse(res, chunks) {
  res.writeHead(200, { 'Content-Type': 'text/event-stream' });

  for (const chunk of chunks) {
    res.write(`data: ${JSON.stringify({ id: 'mock', model: 'mock-model', choices: [{ index: 0, ...chunk }] })}\n\n`);
  }

  res.end('data: [DONE]\n\n');
}

function replyFor(body, tool, args) {
  const toolMessage = [...(body.messages ?? [])].reverse().find((message) => message.role === 'tool');

  if (! toolMessage) {
    const call = { index: 0, id: 'call_1', type: 'function', function: { name: tool, arguments: JSON.stringify(args) } };

    return {
      stream: [{ delta: { role: 'assistant', tool_calls: [call] } }, { delta: {}, finish_reason: 'tool_calls' }],
      message: { role: 'assistant', content: null, tool_calls: [{ ...call, index: undefined }] },
      finish: 'tool_calls',
    };
  }

  const result = JSON.parse(toolMessage.content);
  const text = result.error ? `ERROR: ${result.error}` : `DONE: ${JSON.stringify(result.result)}`;

  return {
    stream: [{ delta: { role: 'assistant', content: text } }, { delta: {}, finish_reason: 'stop' }],
    message: { role: 'assistant', content: text },
    finish: 'stop',
  };
}

function startMockLlm(tool, args) {
  return new Promise((resolve, reject) => {
    const server = http.createServer((req, res) => {
      let raw = '';
      req.on('data', (chunk) => { raw += chunk; });
      req.on('end', () => {
        const body = raw ? JSON.parse(raw) : {};
        const reply = replyFor(body, tool, args);

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

async function withMockPlatform(page, capability, tool, args, run) {
  const label = `E2E Mock LLM ${Date.now()}`;
  const llm = await startMockLlm(tool, args);
  let platformId = null;

  try {
    await ensureAgenticPimEnabled(page);

    const created = await page.request.post('/admin/magic-ai/platforms', {
      headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': await xsrfToken(page.context()) },
      data: { label, provider: 'groq', api_url: llm.url, api_key: 'mock-key', models: 'mock-model', is_default: false, status: true },
    });

    expect(created.ok(), await created.text()).toBeTruthy();

    const grid = await page.request.get('/admin/magic-ai/platforms', { headers: JSON_HEADERS });
    platformId = ((await grid.json()).records ?? []).find((row) => row.label === label)?.id ?? null;

    await navigateTo(page, 'dashboard');
    await page.getByRole('button', { name: 'Open Agenting PIM' }).click();
    await page.getByRole('button', { name: capability }).click();
    await page.locator('select[title="Select AI Platform"]').selectOption({ label: `${label} (Groq)` });

    await run(page.locator('#agenting-pim-panel'));
  } finally {
    if (platformId) {
      await page.request.delete(`/admin/magic-ai/platforms/${platformId}`, {
        headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': await xsrfToken(page.context()) },
      });
    }

    await llm.close();
  }
}

test.describe('Agenting PIM - Export and Import Products capabilities', { tag: '@global-state' }, () => {
  test('exports products to a csv downloadable from the chat and the job tracker', async ({ adminPageWithWidget: page }) => {
    test.setTimeout(120000);

    const sku = `E2E-AGENT-EXPORT-${Date.now()}`;

    try {
      await createSimpleProductRecord(page, sku);

      await withMockPlatform(page, 'Export Products', 'export_products', { skus: sku, format: 'csv' }, async (panel) => {
        await panel.locator('textarea').first().fill(`Export product ${sku} to CSV`);
        await page.getByRole('button', { name: 'Send' }).click();

        await expect(panel).toContainText('DONE:', { timeout: 60000 });

        const downloadPromise = page.waitForEvent('download');
        await panel.getByRole('button', { name: 'Download' }).last().click();
        const download = await downloadPromise;

        expect(download.suggestedFilename()).toBe('products-export.csv');
        expect(fs.readFileSync(await download.path(), 'utf8')).toContain(sku);

        const trackerId = (await panel.textContent()).match(/"tracker_id":(\d+)/)?.[1];
        const tracked = await page.request.get(`/admin/data-transfer/job-tracker/track/download/${trackerId}`);

        expect(tracked.status()).toBe(200);
        expect(await tracked.text()).toContain(sku);
      });
    } finally {
      await deleteProductBySku(page, sku);
    }
  });

  test('imports a product csv in the core export format into the catalog', async ({ adminPageWithWidget: page }, testInfo) => {
    test.setTimeout(120000);

    const sku = `E2E-AGENT-IMPORT-${Date.now()}`;
    const csvPath = testInfo.outputPath('agentic-import.csv');

    fs.writeFileSync(csvPath, [
      'sku;type;status;name;description;short_description;price (USD);price (EUR)',
      `${sku};simple;true;Agentic Import ${sku};Imported description;Imported summary;12.5;11`,
    ].join('\n'));

    try {
      await withMockPlatform(page, 'Bulk Import CSV', 'import_products', {}, async (panel) => {
        await panel.locator('input[type="file"]').setInputFiles(csvPath);
        await panel.locator('textarea').first().fill('Import the products from this CSV');
        await page.getByRole('button', { name: 'Send' }).click();

        await expect(panel).toContainText('DONE:', { timeout: 60000 });
        await expect(panel).not.toContainText('ERROR:');
      });

      let product = null;

      await expect.poll(async () => {
        const grid = await page.request.get('/admin/catalog/products', { headers: JSON_HEADERS, params: { 'filters[sku][]': sku } });
        product = ((await grid.json()).records ?? []).find((row) => row.sku === sku) ?? null;

        return product;
      }, { message: `${sku} was not imported`, timeout: 60000 }).toBeTruthy();

      await page.goto(`/admin/catalog/products/edit/${product.product_id}`);

      await expect(page.locator('input[name$="[price][USD]"]').first()).toHaveValue(/^12\.50?$/);
    } finally {
      await deleteProductBySku(page, sku);
    }
  });
});
