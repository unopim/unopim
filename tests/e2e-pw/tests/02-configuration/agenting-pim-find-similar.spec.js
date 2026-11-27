const http = require('http');
const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');
const { xsrfToken } = require('../../utils/category-field-helpers');
const { ensureAgenticPimEnabled } = require('../../utils/magic-ai-helpers');
const { createSimpleProductRecord, deleteProductBySku } = require('../../utils/product-helpers');

// The Find Similar capability must resolve a SKU typed in a different letter case.
// PostgreSQL compares SKUs case-sensitively, so "t-shirt" never matched "T-Shirt" there.
// A local Groq-compatible LLM calls find_similar_products and echoes its result,
// and Groq has no embeddings API, so the keyword fallback ranking is exercised.
// Needs WEBHOOK_ALLOW_LOOPBACK=true on the app so the platform may target 127.0.0.1.

const JSON_HEADERS = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

function sse(res, chunks) {
  res.writeHead(200, { 'Content-Type': 'text/event-stream' });

  for (const chunk of chunks) {
    res.write(`data: ${JSON.stringify({ id: 'mock', model: 'mock-model', choices: [{ index: 0, ...chunk }] })}\n\n`);
  }

  res.end('data: [DONE]\n\n');
}

function replyFor(body, sourceSku) {
  const toolMessage = [...(body.messages ?? [])].reverse().find((message) => message.role === 'tool');

  if (! toolMessage) {
    const call = { index: 0, id: 'call_1', type: 'function', function: { name: 'find_similar_products', arguments: JSON.stringify({ sku: sourceSku }) } };

    return {
      stream: [{ delta: { role: 'assistant', tool_calls: [call] } }, { delta: {}, finish_reason: 'tool_calls' }],
      message: { role: 'assistant', content: null, tool_calls: [{ ...call, index: undefined }] },
      finish: 'tool_calls',
    };
  }

  const result = JSON.parse(toolMessage.content);
  const text = result.error
    ? `ERROR: ${result.error}`
    : `SIMILAR: ${(result.products ?? []).map((product) => product.sku).join(', ')}`;

  return {
    stream: [{ delta: { role: 'assistant', content: text } }, { delta: {}, finish_reason: 'stop' }],
    message: { role: 'assistant', content: text },
    finish: 'stop',
  };
}

function startMockLlm(sourceSku) {
  return new Promise((resolve, reject) => {
    const server = http.createServer((req, res) => {
      let raw = '';
      req.on('data', (chunk) => { raw += chunk; });
      req.on('end', () => {
        const body = raw ? JSON.parse(raw) : {};
        const reply = replyFor(body, sourceSku);

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

test.describe('Agenting PIM - Find Similar capability', { tag: '@global-state' }, () => {
  test('finds similar products for a SKU typed in a different letter case', async ({ adminPageWithWidget }) => {
    test.setTimeout(120000);

    const stamp = Date.now();
    const sourceSku = `E2E-Similar-Shirt-${stamp}`;
    const similarSku = `e2e-similar-shirt-${stamp}-blue`;
    const label = `E2E Mock LLM ${stamp}`;
    const llm = await startMockLlm(sourceSku.toLowerCase());
    let platformId = null;

    try {
      await ensureAgenticPimEnabled(adminPageWithWidget);
      await createSimpleProductRecord(adminPageWithWidget, sourceSku);
      await createSimpleProductRecord(adminPageWithWidget, similarSku);

      const created = await adminPageWithWidget.request.post('/admin/magic-ai/platforms', {
        headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': await xsrfToken(adminPageWithWidget.context()) },
        data: { label, provider: 'groq', api_url: llm.url, api_key: 'mock-key', models: 'mock-model', is_default: false, status: true },
      });

      expect(created.ok(), await created.text()).toBeTruthy();

      const grid = await adminPageWithWidget.request.get('/admin/magic-ai/platforms', { headers: JSON_HEADERS });
      platformId = ((await grid.json()).records ?? []).find((row) => row.label === label)?.id ?? null;

      await navigateTo(adminPageWithWidget, 'dashboard');
      await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
      await adminPageWithWidget.getByRole('button', { name: 'Find Similar' }).click();

      await adminPageWithWidget.locator('select[title="Select AI Platform"]').selectOption({ label: `${label} (Groq)` });
      await adminPageWithWidget.locator('#agenting-pim-panel textarea').first().fill(`Find products similar to SKU ${sourceSku.toLowerCase()}`);
      await adminPageWithWidget.getByRole('button', { name: 'Send' }).click();

      const panel = adminPageWithWidget.locator('#agenting-pim-panel');

      await expect(panel).toContainText('SIMILAR:', { timeout: 60000 });
      await expect(panel).toContainText(similarSku);
      await expect(panel).not.toContainText('SKU not found');
    } finally {
      if (platformId) {
        await adminPageWithWidget.request.delete(`/admin/magic-ai/platforms/${platformId}`, {
          headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': await xsrfToken(adminPageWithWidget.context()) },
        });
      }

      await deleteProductBySku(adminPageWithWidget, sourceSku);
      await deleteProductBySku(adminPageWithWidget, similarSku);
      await llm.close();
    }
  });
});
