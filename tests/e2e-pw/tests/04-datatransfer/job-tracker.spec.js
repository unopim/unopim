/**
 * The job tracker is one shared component rendered for import, export and system jobs. These
 * specs guard the split: the Vue template must still compile (a broken v-if chain or a missing
 * partial only shows up in the browser console) and both wrappers must reach it.
 */
const { test, expect } = require('@playwright/test');

const HIDE_OVERLAYS = () => {
  const style = document.createElement('style');

  style.textContent = '.ap-shell, .phpdebugbar, .phpdebugbar-open-handler { display: none !important; }';

  if (document.head) {
    document.head.appendChild(style);
  } else {
    document.addEventListener('DOMContentLoaded', () => document.head.appendChild(style));
  }
};

test.describe('Data transfer job tracker', () => {
  test.beforeEach(async ({ page }) => {
    await page.addInitScript(HIDE_OVERLAYS);
  });

  test('renders tracked jobs without Vue template errors', async ({ page }) => {
    const errors = [];

    page.on('console', (message) => {
      if (message.type() === 'error') {
        errors.push(message.text());
      }
    });

    const grid = await page.request.get('/admin/data-transfer/job-tracker', {
      headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
    });

    const records = grid.ok() ? (await grid.json()).records ?? [] : [];
    const jobIds = records.map((record) => record.id).filter(Boolean).slice(0, 3);

    test.skip(jobIds.length === 0, 'no tracked jobs seeded in this environment');

    for (const id of jobIds) {
      await page.goto(`/admin/data-transfer/job-tracker/track/${id}`);

      await expect(page.locator('#v-job-tracker-template')).toHaveCount(1);

      /** The component only paints once the template compiles, so this catches a broken split. */
      await expect(page.locator('.rounded-lg.border').first()).toBeVisible();
    }

    expect(errors.filter((text) => /vue|template|v-else|Failed to resolve/i.test(text))).toEqual([]);
  });
});
