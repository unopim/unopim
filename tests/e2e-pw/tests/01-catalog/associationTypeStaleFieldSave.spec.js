const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');

test.describe('Association Type - Stale Field Id Save (#1244)', () => {
  test.setTimeout(90000);

  test('should not fail with __clone method called on non-object', async ({ adminPage }) => {
    const uid = generateUid();
    const typeCode = `staleid_${uid}`;

    await navigateTo(adminPage, 'associationTypes');

    await adminPage.getByRole('button', { name: 'Create Association Type' }).click();
    await adminPage.waitForTimeout(500);
    await adminPage.locator('input[name="code"]').last().fill(typeCode);
    await adminPage.getByRole('button', { name: 'Save Association Type' }).click();
    await adminPage.waitForLoadState('domcontentloaded');
    await adminPage.waitForTimeout(1000);

    await expect(adminPage).toHaveURL(/association-types\/edit\/\d+/);

    const result = await adminPage.evaluate(async () => {
      const form = document.getElementById('association-type-edit-form');
      const body = new FormData(form);

      body.set('fields[99999][isNew]', 'false');
      body.set('fields[99999][isDelete]', 'true');
      body.set('fields[99999][code]', 'ghost_field');
      body.set('fields[99999][type]', 'text');

      const response = await fetch(form.action, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', Accept: 'application/json' },
        body,
      });

      return { status: response.status, text: (await response.text()).slice(0, 400) };
    });

    expect(result.status).toBe(200);
    expect(result.text).not.toContain('__clone');
  });
});
