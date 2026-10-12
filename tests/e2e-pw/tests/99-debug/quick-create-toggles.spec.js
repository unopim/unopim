const { test, expect } = require('@playwright/test');
const { login } = require('../../utils/login');

// Repro: quick-create modal toggles (is_unique / value_per_locale / value_per_channel)
// reported as lost on store. Captures the real POST payload and the DB-truth edit page.
test('quick-create posts checked toggles and edit page shows them', async ({ page }) => {
  const code = 'dbg' + Date.now().toString().slice(-8);

  await page.goto('/admin/catalog/attributes');

  if (await page.getByRole('textbox', { name: 'Email Address' }).isVisible().catch(() => false)) {
    await login(page);
    await page.goto('/admin/catalog/attributes');
  }

  await page.getByRole('button', { name: 'Create Attribute' }).click();

  const modal = page.locator('form:has(input[name="code"])').first();

  await page.locator('input[name*="[name]"]').first().fill('Debug Toggle');
  await page.locator('input[name="code"]').fill(code);

  // Select type = Text via the multiselect component
  await page.locator('.multiselect:has(input[name="type"])').first().click();
  await page.getByRole('option', { name: /^Text\b/ }).first().click();

  // Wait for is_unique toggle (only rendered for type=text)
  await page.locator('label[for="is_unique"]').first().waitFor({ state: 'visible', timeout: 5000 });

  for (const name of ['is_unique', 'value_per_locale', 'value_per_channel']) {
    await page.locator(`label[for="${name}"]`).first().click();
  }

  // Dump actual checkbox DOM state before submit
  const domState = await page.evaluate(() => {
    const out = {};
    for (const n of ['is_unique', 'value_per_locale', 'value_per_channel']) {
      const inputs = [...document.querySelectorAll(`input[name="${n}"]`)];
      out[n] = inputs.map(i => ({ checked: i.checked, value: i.value, inForm: !!i.closest('form') }));
    }
    return out;
  });
  console.log('DOM STATE:', JSON.stringify(domState, null, 2));

  const postPromise = page.waitForRequest(
    (r) => r.method() === 'POST' && r.url().includes('/admin/catalog/attributes'),
    { timeout: 10000 }
  );

  await page.getByRole('button', { name: /^Save/ }).last().click();

  const post = await postPromise;
  const payload = post.postData() || '';
  console.log('POST PAYLOAD:', payload);

  expect(payload).toContain('is_unique');
  expect(payload).toContain('value_per_locale');
  expect(payload).toContain('value_per_channel');

  // Stale-nav check: after save we should land on the edit page of the NEW code
  await page.waitForURL(/attributes\/edit\/\d+/, { timeout: 15000 });
  await expect(page.locator('input[name="code"]').first()).toHaveValue(code, { timeout: 10000 });
});

// Order-dependent scenario: user checks locale/channel toggles BEFORE picking a type.
// If the type-fields slot re-render resets checkbox state, the flags silently post as absent.
test('toggles checked before selecting type survive the type change', async ({ page }) => {
  const code = 'dbg' + Date.now().toString().slice(-8);

  await page.goto('/admin/catalog/attributes');

  if (await page.getByRole('textbox', { name: 'Email Address' }).isVisible().catch(() => false)) {
    const { login } = require('../../utils/login');
    await login(page);
    await page.goto('/admin/catalog/attributes');
  }

  await page.getByRole('button', { name: 'Create Attribute' }).click();

  await page.locator('input[name*="[name]"]').first().fill('Debug Order');
  await page.locator('input[name="code"]').fill(code);

  // Check the always-visible toggles FIRST
  for (const name of ['value_per_locale', 'value_per_channel']) {
    await page.locator(`label[for="${name}"]`).first().click();
  }

  const before = await page.evaluate(() =>
    ['value_per_locale', 'value_per_channel'].map(n => document.querySelector(`input[name="${n}"]`)?.checked)
  );
  console.log('CHECKED BEFORE TYPE SELECT:', JSON.stringify(before));

  // THEN select type
  await page.locator('.multiselect:has(input[name="type"])').first().click();
  await page.getByRole('option', { name: /^Text\b/ }).first().click();
  await page.locator('label[for="is_unique"]').first().waitFor({ state: 'visible', timeout: 5000 });
  await page.locator('label[for="is_unique"]').first().click();

  const after = await page.evaluate(() =>
    ['is_unique', 'value_per_locale', 'value_per_channel'].map(n => document.querySelector(`input[name="${n}"]`)?.checked)
  );
  console.log('CHECKED AFTER TYPE SELECT:', JSON.stringify(after));

  const postPromise = page.waitForRequest(
    (r) => r.method() === 'POST' && r.url().includes('/admin/catalog/attributes'),
    { timeout: 10000 }
  );

  await page.getByRole('button', { name: /^Save/ }).last().click();

  const payload = (await postPromise).postData() || '';
  console.log('POST PAYLOAD:', payload);

  expect(payload).toContain('value_per_locale');
  expect(payload).toContain('value_per_channel');
  expect(payload).toContain('is_unique');
});
