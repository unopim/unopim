const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

/**
 * Only the create form enforces the `[A-Za-z0-9_-]` code format, so jobs seeded or created before
 * that rule can carry a space. Those are the jobs whose edits used to be rejected.
 */
test.describe('Product Export - Filter Persistence (#1182)', () => {
  test.setTimeout(150000);

  test('should persist export filters for a job whose code contains a space', async ({ adminPage }) => {
    await navigateTo(adminPage, 'exports');

    const target = await adminPage.evaluate(async () => {
      const response = await fetch('/admin/data-transfer/exports?datagrid=true', {
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
      });

      const body = await response.json();

      return (body.records || []).find((record) => /\s/.test(record.code ?? '')) ?? null;
    });

    if (!target) {
      test.skip(true, 'No export job with a space in its code on this instance');
    }

    await adminPage.goto(`/admin/data-transfer/exports/edit/${target.id}`, { waitUntil: 'domcontentloaded' });
    await adminPage.waitForTimeout(1500);

    const original = await adminPage.evaluate(
      () => document.querySelector('#export-profile-edit-form input[type="hidden"][name="filters[file_format]"]')?.value
    );

    const next = original === 'Xlsx' ? 'Csv' : 'Xlsx';

    const saved = await adminPage.evaluate(async (fileFormat) => {
      const form = document.getElementById('export-profile-edit-form');
      const body = new FormData(form);

      body.set('filters[file_format]', fileFormat);

      const response = await fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        body,
      });

      const text = await response.text();

      return { status: response.status, body: text.startsWith('<!DOCTYPE') ? 'redirect' : text.slice(0, 300) };
    }, next);

    expect(saved.status).toBe(200);
    expect(saved.body).not.toContain('code format is invalid');

    await adminPage.goto(`/admin/data-transfer/exports/edit/${target.id}`, { waitUntil: 'domcontentloaded' });
    await adminPage.waitForTimeout(1200);

    const persisted = await adminPage.evaluate(
      () => document.querySelector('#export-profile-edit-form input[type="hidden"][name="filters[file_format]"]')?.value
    );

    expect(persisted).toBe(next);

    await adminPage.evaluate(async (fileFormat) => {
      const form = document.getElementById('export-profile-edit-form');
      const body = new FormData(form);

      body.set('filters[file_format]', fileFormat);

      await fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        body,
      });
    }, original);
  });
});
