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
 * Switch one Magic AI section's `enabled` flag through a direct form POST,
 * which avoids racing the Vue toggle's render. Store-wide: a spec calling this
 * carries @global-state.
 * @param {import('@playwright/test').Page} page
 * @param {'settings'|'translation'} section
 * @param {boolean} enabled
 */
async function setMagicAISection(page, section, enabled) {
  const field = `general[magic_ai][${section}][enabled]`;
  const checkbox = `input[type="checkbox"][name="${field}"]`;

  await gotoPage(page, MAGIC_AI_CONFIG_URL);
  await page.locator(checkbox).first().waitFor({ state: 'attached', timeout: 10000 });

  const result = await page.evaluate(async ({ name, on }) => {
    const cb = document.querySelector(`input[type="checkbox"][name="${name}"]`);
    const form = cb?.closest('form');

    if (! form) {
      return { ok: false, reason: 'no-form' };
    }

    cb.checked = on;
    cb.dispatchEvent(new Event('change', { bubbles: true }));

    const body = new FormData(form);
    body.set(name, on ? '1' : '0');

    const xsrf = (document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN=')) || '').split('=')[1] || '';
    const res = await fetch(form.getAttribute('action') || window.location.pathname, {
      method: 'POST',
      credentials: 'same-origin',
      headers: { 'X-XSRF-TOKEN': decodeURIComponent(xsrf), Accept: 'text/html,*/*' },
      body,
      redirect: 'follow',
    });

    return { ok: res.ok, status: res.status };
  }, { name: field, on: enabled });

  expect(result, `Setting Magic AI ${section} to ${enabled} failed: ${JSON.stringify(result)}`).toMatchObject({ ok: true });

  await gotoPage(page, MAGIC_AI_CONFIG_URL);

  const state = expect(page.locator(checkbox).first());
  await (enabled ? state.toBeChecked({ timeout: 10000 }) : state.not.toBeChecked({ timeout: 10000 }));
}

/** Turn on Magic AI text generation, which gates the Magic AI buttons. */
const ensureMagicAITextEnabled = (page) => setMagicAISection(page, 'settings', true);

/** Turn on Magic AI translation, which gates the product "More" actions. */
const ensureMagicAITranslationEnabled = (page) => setMagicAISection(page, 'translation', true);

/**
 * Turn off Magic AI translation. Enabled translation makes its channel and
 * locale pickers required, so a spec saving the settings form through the UI
 * needs it off when an earlier spec left it on without those values.
 */
const ensureMagicAITranslationDisabled = (page) => setMagicAISection(page, 'translation', false);

module.exports = {
  ensureAiPlatform,
  ensureMagicAITextEnabled,
  ensureMagicAITranslationEnabled,
  ensureMagicAITranslationDisabled,
};
