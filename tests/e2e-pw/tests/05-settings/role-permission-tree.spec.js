const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

/**
 * Guards the ACL permission tree on the role form: clicking a folder's chevron
 * must reveal its child permissions. Regression cover for the toggle handler,
 * which resolves the `.v-tree-item` ancestor via `closest()` — a plain
 * `parentElement` lookup breaks once the row is wrapped for layout.
 */
test.describe('Role permission tree', () => {
  test('expanding a folder chevron reveals its child permissions', async ({ adminPage }) => {
    await navigateTo(adminPage, 'roles');
    await adminPage.getByRole('link', { name: 'Create Role' }).click();
    await adminPage.waitForLoadState('networkidle');

    const catalog = adminPage
      .locator('.v-tree-item')
      .filter({ has: adminPage.getByText('Catalog', { exact: true }) })
      .first();

    await expect(catalog).toBeVisible();

    // `active` is a standalone token; the tree's Tailwind classes also contain
    // the literal `[&.active>...]` variant, so assert on the token, not a substring.
    const isActive = () => catalog.evaluate((el) => el.classList.contains('active'));

    expect(await isActive()).toBe(false);

    await catalog.locator('i.icon-chevron-right').first().click();

    await expect.poll(isActive).toBe(true);
    await expect(catalog.getByText('Products', { exact: true }).first()).toBeVisible();
  });
});
