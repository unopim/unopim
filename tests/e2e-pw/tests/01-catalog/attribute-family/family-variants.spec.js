const { test, expect } = require('../../../utils/family-fixtures');
const { generateUid } = require('../../../utils/helpers');
const { createFamily, deleteFamilyByCode, gotoTab, withFamilyPage } = require('../../../utils/family-helpers');

// Variant structures require configurable (non-scoped select) axis attributes.
// The seeded 'default' family (id 1) has color/size/brand, so we clone from it.
test.describe.serial('Attribute Family — Variants tab', () => {
  let family;

  test.beforeAll(async ({ browser }) => {
    family = await withFamilyPage(browser, (page) => createFamily(page, `famvar_${generateUid()}`, { basedOn: 'default' }));
  });

  test.afterAll(async ({ browser }) => {
    await withFamilyPage(browser, (page) => deleteFamilyByCode(page, family.code).catch(() => {}));
  });

  test('variants tab renders with Add Variant', async ({ adminPage }) => {
    await gotoTab(adminPage, family.id, 'variants');
    await expect(adminPage.getByText('Add Variant', { exact: true }).first()).toBeVisible({ timeout: 20000 });
  });

  test('create a level-1 (Parent → Child) structure → redirects to editor + shows in grid', async ({ adminPage }) => {
    const page = adminPage;
    await gotoTab(page, family.id, 'variants');

    const name = `Struct ${generateUid()}`;
    await page.getByText('Add Variant', { exact: true }).first().click();
    await page.getByPlaceholder('Example: Color + Size').fill(name);
    await page.getByPlaceholder('color_size').fill(`s${generateUid()}`);
    // level_1 axis is prefilled from the first axis option; save.
    await Promise.all([
      page.waitForURL(/\/variant-structures\/\d+\/edit/, { timeout: 25000 }),
      page.getByRole('button', { name: 'Save Variant' }).click(),
    ]);
    // Redirected to the ownership editor for the new structure.
    await expect(page).toHaveURL(/\/variant-structures\/\d+\/edit/);

    // Structure now listed in the grid.
    await gotoTab(page, family.id, 'variants');
    await expect(page.locator('#app').getByText(name, { exact: false }).first()).toBeVisible({ timeout: 20000 });
  });

  test('validation: name/code required warning', async ({ adminPage }) => {
    const page = adminPage;
    await gotoTab(page, family.id, 'variants');

    await page.getByText('Add Variant', { exact: true }).first().click();
    await page.getByPlaceholder('Example: Color + Size').fill('');
    await page.getByRole('button', { name: 'Save Variant' }).click();
    await expect(page.locator('#app').getByText(/Name and code are required/i)).toBeVisible({ timeout: 10000 });
  });

  test('create a level-2 (Parent → Sub-parent → Child) structure', async ({ adminPage }) => {
    const page = adminPage;
    await gotoTab(page, family.id, 'variants');

    const name = `L2 ${generateUid()}`;
    await page.getByText('Add Variant', { exact: true }).first().click();
    await page.getByPlaceholder('Example: Color + Size').fill(name);
    await page.getByPlaceholder('color_size').fill(`s${generateUid()}`);
    await page.getByText('Parent → Sub-parent → Child', { exact: true }).click();
    await Promise.all([
      page.waitForURL(/\/variant-structures\/\d+\/edit/, { timeout: 25000 }),
      page.getByRole('button', { name: 'Save Variant' }).click(),
    ]);
    await expect(page).toHaveURL(/\/variant-structures\/\d+\/edit/);
  });

  test('editor: ownership page renders columns and saves', async ({ adminPage }) => {
    const page = adminPage;
    // Create a fresh structure to land on the editor.
    await gotoTab(page, family.id, 'variants');
    const name = `Own ${generateUid()}`;
    await page.getByText('Add Variant', { exact: true }).first().click();
    await page.getByPlaceholder('Example: Color + Size').fill(name);
    await page.getByPlaceholder('color_size').fill(`s${generateUid()}`);
    await Promise.all([
      page.waitForURL(/\/variant-structures\/\d+\/edit/, { timeout: 25000 }),
      page.getByRole('button', { name: 'Save Variant' }).click(),
    ]);

    // Ownership editor mounts (SPA render can lag on a large catalog).
    await expect(page.getByRole('button', { name: 'Save Variant' })).toBeVisible({ timeout: 30000 });
    await expect(page.locator('#app').getByText(/Parent Product/i).first()).toBeVisible({ timeout: 15000 });
    await expect(page.locator('#app').getByText(/Child Product/i).first()).toBeVisible();

    // Saving from the editor persists ownership and redirects back to the list.
    await Promise.all([
      page.waitForURL(/\/attribute-families\/edit\/\d+\?variants/, { timeout: 20000 }),
      page.getByRole('button', { name: 'Save Variant' }).click(),
    ]);
    await expect(page.getByText('Add Variant', { exact: true }).first()).toBeVisible({ timeout: 15000 });
  });

  test('delete a variant structure from the grid', async ({ adminPage }) => {
    const page = adminPage;
    await gotoTab(page, family.id, 'variants');

    const delIcon = page.locator('span[title="Delete Variant"], span[title="Delete"]').first();
    await delIcon.waitFor({ state: 'visible', timeout: 20000 });
    await delIcon.click();
    await page.getByRole('button', { name: /^(Delete|Agree)$/ }).first().click();
    await expect(page.locator('#app').getByText(/deleted|removed|success/i).first()).toBeVisible({ timeout: 15000 });
  });
});
