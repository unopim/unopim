const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, expectSuccessToast, waitForAppReady } = require('../../utils/helpers');

test.describe('Association Type - Locale Label Validation (#1238)', () => {
  test.setTimeout(90000);

  test('should save an association type that has untranslated locale labels', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `loclabel_${uid}`;

    await navigateTo(adminPage, 'associationTypes');

    await adminPage.getByRole('button', { name: 'Create Association Type' }).click();

    const codeInput = adminPage.locator('input[name="code"]').last();
    await codeInput.waitFor({ state: 'visible' });
    await codeInput.fill(code);

    await adminPage.getByRole('button', { name: 'Save Association Type' }).click();

    await expect(adminPage).toHaveURL(/association-types\/edit\/\d+/);
    await waitForAppReady(adminPage);

    await adminPage.locator('input[name="position"]').fill('7');
    await adminPage.locator('input[name="position"]').press('Tab');

    const saveBtn = adminPage.getByRole('button', { name: 'Save changes' });
    await saveBtn.waitFor({ state: 'visible', timeout: 15000 });
    await saveBtn.click();

    await expectSuccessToast(adminPage, /updated successfully/i);

    const bodyText = await adminPage.textContent('body');
    expect(bodyText).not.toMatch(/\.name field is required/i);
  });
});
