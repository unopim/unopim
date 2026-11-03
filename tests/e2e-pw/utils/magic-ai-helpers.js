// utils/magic-ai-helpers.js — Magic AI preconditions a spec sets up itself instead of relying on run order.

const { expect } = require('@playwright/test');
const { xsrfToken } = require('./category-field-helpers');
const { gotoPage } = require('./helpers');

const MAGIC_AI_CONFIG_URL = '/admin/magic-ai/settings';

const JSON_HEADERS = { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' };

/**
 * Make sure an enabled default AI platform exists. The platform pickers and
 * their help text render only when one does, and the spec that used to create
 * it ran earlier in the same shard only by chance of file order.
 * @param {import('@playwright/test').Page} page
 * @param {string} apiKey
 */
async function ensureAiPlatform(page, apiKey) {
  if (! (await xsrfToken(page.context()))) {
    await page.goto('/admin/dashboard', { waitUntil: 'domcontentloaded' });
  }

  const grid = await page.request.get('/admin/magic-ai/platforms', { headers: JSON_HEADERS });

  if (((await grid.json()).records ?? []).length) {
    return;
  }

  const created = await page.request.post('/admin/magic-ai/platforms', {
    headers: { ...JSON_HEADERS, 'X-XSRF-TOKEN': await xsrfToken(page.context()) },
    data: {
      label: 'E2E OpenAI',
      provider: 'openai',
      api_key: apiKey,
      models: 'gpt-4o-mini',
      is_default: true,
      status: true,
    },
  });

  if (! created.ok()) {
    throw new Error(`Creating the AI platform failed: HTTP ${created.status()} ${await created.text()}`);
  }
}

/**
 * Switch one Magic AI section's `enabled` flag on through a direct form POST,
 * which avoids racing the Vue toggle's render. Store-wide: a spec calling this
 * carries @global-state.
 * @param {import('@playwright/test').Page} page
 * @param {'settings'|'translation'} section
 */
async function enableMagicAISection(page, section) {
  const field = `general[magic_ai][${section}][enabled]`;
  const checkbox = `input[type="checkbox"][name="${field}"]`;

  await gotoPage(page, MAGIC_AI_CONFIG_URL);
  await page.locator(checkbox).first().waitFor({ state: 'attached', timeout: 10000 });

  const result = await page.evaluate(async (name) => {
    const cb = document.querySelector(`input[type="checkbox"][name="${name}"]`);
    const form = cb?.closest('form');

    if (! form) {
      return { ok: false, reason: 'no-form' };
    }

    cb.checked = true;
    cb.dispatchEvent(new Event('change', { bubbles: true }));

    const xsrf = (document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1] || '';
    const res = await fetch(form.getAttribute('action') || window.location.pathname, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-XSRF-TOKEN': decodeURIComponent(xsrf), Accept: 'text/html,*/*' },
      body: new FormData(form),
      redirect: 'follow',
    });

    return { ok: res.ok, status: res.status };
  }, field);

  expect(result, `Enabling Magic AI ${section} failed: ${JSON.stringify(result)}`).toMatchObject({ ok: true });

  await gotoPage(page, MAGIC_AI_CONFIG_URL);
  await expect(page.locator(checkbox).first()).toBeChecked({ timeout: 10000 });
}

/** Turn on Magic AI text generation, which gates the Magic AI buttons. */
const ensureMagicAITextEnabled = (page) => enableMagicAISection(page, 'settings');

/** Turn on Magic AI translation, which gates the product "More" actions. */
const ensureMagicAITranslationEnabled = (page) => enableMagicAISection(page, 'translation');

module.exports = { ensureAiPlatform, ensureMagicAITextEnabled, ensureMagicAITranslationEnabled };
