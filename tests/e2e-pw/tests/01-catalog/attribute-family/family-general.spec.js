const { test, expect } = require('../../../utils/family-fixtures');
const { generateUid } = require('../../../utils/helpers');
const {
  INDEX_PATH, gotoIndex, createFamily, deleteFamilyByCode, assignGroup, saveFamilyEdit,
} = require('../../../utils/family-helpers');

test.describe('Attribute Family — General tab & index', () => {
  test('index: required-field validation, search, filter', async ({ adminPage }) => {
    const page = adminPage;
    await gotoIndex(page);

    // Required-field validation (Name + Code both required)
    await page.getByRole('button', { name: 'Create Attribute Family' }).click();
    await page.getByPlaceholder('Enter Name').fill('');
    await page.getByPlaceholder('Enter Code').fill('');
    await page.getByRole('button', { name: 'Save Attribute Family' }).click();
    await expect(page.locator('#app').getByText(/The (Name|Code) field is required/).first()).toBeVisible();
    await page.keyboard.press('Escape').catch(() => {});

    // Search seeded 'default'
    await gotoIndex(page);
    await page.getByRole('textbox', { name: 'Search' }).fill('default');
    await page.keyboard.press('Enter');
    await page.waitForTimeout(1500);
    await expect(page.locator('#app').getByText('default', { exact: true }).first()).toBeVisible();

    // Filter menu
    await page.getByText('Filter', { exact: true }).click();
    await expect(page.locator('#app').getByText('Apply Filters')).toBeVisible();
  });

  test('create then delete a family', async ({ adminPage }) => {
    const page = adminPage;
    const code = `fam_${generateUid()}`;

    const { id } = await createFamily(page, code);
    expect(id).toMatch(/^\d+$/);
    await expect(page).toHaveURL(/\/attribute-families\/edit\/\d+/);

    await deleteFamilyByCode(page, code);
    await gotoIndex(page);
    await page.getByRole('textbox', { name: 'Search' }).fill(code);
    await page.keyboard.press('Enter');
    await page.waitForTimeout(1500);
    await expect(page.locator('#app').getByText(code, { exact: true })).toHaveCount(0);
  });

  test('edit general: four tabs render + scaffolded General group holds SKU', async ({ adminPage }) => {
    const page = adminPage;
    const { code } = await createFamily(page);

    await expect(page.getByRole('link', { name: 'Variants' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Completeness' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'History' })).toBeVisible();
    await expect(page.getByText('General', { exact: true }).first()).toBeVisible();

    await expect(page.locator('#assigned-attribute-groups').getByText('General', { exact: true }).first()).toBeVisible();
    await expect(page.locator('#assigned-attribute-groups').getByText('SKU', { exact: true }).first()).toBeVisible();

    await deleteFamilyByCode(page, code);
  });

  test('edit general: change label name and save', async ({ adminPage }) => {
    const page = adminPage;
    const { code } = await createFamily(page);

    const nameInput = page.locator('input[name$="[name]"]').first();
    await nameInput.fill(`Renamed ${code}`);
    await nameInput.blur();
    await saveFamilyEdit(page);

    await deleteFamilyByCode(page, code);
  });

  test('edit general: assign a new attribute group then save', async ({ adminPage }) => {
    const page = adminPage;
    const { code } = await createFamily(page);
    const groupsBefore = await page.locator('.group_node').count();

    await assignGroup(page); // pick first available group
    await expect(page.locator('.group_node')).toHaveCount(groupsBefore + 1);

    await saveFamilyEdit(page);
    await deleteFamilyByCode(page, code);
  });

  test('edit: clicking a section search icon auto-focuses the revealed input', async ({ adminPage }) => {
    test.slow(); // create + two-panel interaction + cleanup runs long on the seeded DB
    const page = adminPage;
    const { code } = await createFamily(page);

    // Both panels start with a magnifying-glass toggle; clicking it must reveal
    // the search field AND focus it so the user can type without a second click.
    for (const title of ['Assigned Groups', 'Unassigned Attributes']) {
      const panel = page.locator('div.mb-4').filter({ hasText: title }).first();

      await panel.locator('button.icon-search').click();

      const input = panel.getByRole('textbox', { name: 'Search' });
      await expect(input).toBeVisible();
      await expect(input).toBeFocused();

      // Typing lands in the field directly — proves focus, not just visibility.
      await page.keyboard.type('sku');
      await expect(input).toHaveValue('sku');
    }

    await deleteFamilyByCode(page, code);
  });

  test('edit: searched unassigned attribute checkbox selects on first click', async ({ adminPage }) => {
    test.slow();
    const page = adminPage;
    const { code } = await createFamily(page);

    const panel = page.locator('div.mb-4').filter({ hasText: 'Unassigned Attributes' }).first();
    await panel.locator('button.icon-search').click();

    const input = panel.getByRole('textbox', { name: 'Search' });
    await input.fill('name');
    await page.keyboard.press('Enter');
    await page.waitForTimeout(2500); // search round-trip

    const row = page.locator('#unassigned-attributes div.group', { hasText: /name/i }).first();
    await expect(row).toBeVisible();

    // A single click must select it — clicking the checkbox blurs the search
    // input, which used to re-run the query and tear the list down mid-click.
    const checkbox = row.locator('button').first();
    await checkbox.click();
    await expect(checkbox).toHaveClass(/icon-checkbox-check/);

    await deleteFamilyByCode(page, code);
  });
});
