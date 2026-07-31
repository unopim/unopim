const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect } = require('../../utils/helpers');

/**
 * Opens the Create Category form and lets the unsaved-changes tracker snapshot
 * the pristine form before any field is touched (mirrors category.spec.js).
 *
 * The tree/list toggle is sticky per session, so a previous test's cleanup
 * (which searches the flat list to delete its fixture) leaves the *next*
 * test's plain `/categories` visit rendering the list view instead of the
 * tree — and "Add Category" then opens the standalone create page instead of
 * the ajax tree panel, whose save never lands on a `?category=` redirect.
 * Forcing `view=tree` here makes the tree the one this test always gets.
 */
async function openCreateForm(adminPage) {
  await adminPage.goto('/admin/catalog/categories?view=tree', { waitUntil: 'networkidle' });
  await adminPage.getByRole('link', { name: 'Add Category' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.locator('input[name="code"]').waitFor({ state: 'visible' });
  await adminPage.waitForTimeout(1200);
}

/**
 * Creates a root-level category via the UI and returns its id, read off the
 * `?category=<id>` redirect the store action lands on.
 */
async function createCategory(adminPage, code, name) {
  await openCreateForm(adminPage);
  await adminPage.locator('input[name="code"]').fill(code);
  await adminPage.locator('#name').fill(name);
  await clickSaveAndExpect(adminPage, 'Save changes', /category created successfully/i);
  await expect
    .poll(() => adminPage.url(), { timeout: 20000 })
    .toMatch(/[?&]category=\d+/);

  const match = adminPage.url().match(/[?&]category=(\d+)/);

  return match ? Number(match[1]) : null;
}

/**
 * Deletes a category by code through the flat listing. Best-effort: this only
 * ever runs from a finally block, so it must not mask the test's real result.
 */
async function deleteCategory(adminPage, code) {
  try {
    await navigateTo(adminPage, 'categoriesList');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(code);
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');

    const deleteBtn = adminPage.locator('div', { hasText: code }).locator('span[title="Delete"]').first();

    if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await deleteBtn.click();
      await adminPage.locator('.max-w-\\[400px\\]').getByRole('button', { name: 'Delete', exact: true }).click();
      await adminPage.waitForLoadState('networkidle');
    }
  } catch {}
}

/**
 * The parent-picker toggle, found by the stable "Parent" field label rather
 * than by its own displayed value. The old locator filtered on the toggle's
 * own text ("Root level" or a "/"-joined breadcrumb) — but a one-level-deep
 * breadcrumb is a single segment with no "/", so the instant a pick landed on
 * such a category the locator stopped matching its own element and every
 * later read/click on it hung. Scoping by the field's label sidesteps that
 * entirely: the toggle resolves the same way no matter what it currently shows.
 */
function parentDrawerToggle(adminPage) {
  return adminPage.locator('[data-control-group]', {
    has: adminPage.getByText('Parent', { exact: true }),
  }).locator('div.cursor-pointer').first();
}

test.describe('Category parent picker', () => {
  test.setTimeout(90000);

  /**
   * The seeded root category is never a safe fixture for this: it renders its
   * own picker with no other selectable node, so a fresh install (root, no
   * children) offers nothing to switch to. Two throwaway root-level
   * categories are created instead — editing one always leaves the other as
   * a valid, enabled pick, independent of whatever else the database holds.
   */
  test('updates the parent field via search and closes the drawer', async ({ adminPage }) => {
    const uid = generateUid();
    const codeA = `catppa_${uid}`;
    const codeB = `catppb_${uid}`;
    const nameB = `Picker B ${uid}`;

    try {
      const idA = await createCategory(adminPage, codeA, `Picker A ${uid}`);
      await createCategory(adminPage, codeB, nameB);

      await adminPage.goto(`/admin/catalog/categories?category=${idA}`, { waitUntil: 'networkidle' });

      const drawerToggle = parentDrawerToggle(adminPage);

      await expect(drawerToggle).toHaveText('Root level');

      await drawerToggle.click();

      const drawerPanel = adminPage.locator('[data-drawer-panel]');

      const searched = adminPage.waitForResponse((r) => {
        if (! r.url().includes('/catalog/categories/search')) {
          return false;
        }

        return new URL(r.url()).searchParams.get('query') === codeB;
      });
      await drawerPanel.getByPlaceholder('Search categories').fill(codeB);
      await searched;

      await drawerPanel.getByText(nameB, { exact: true }).first().click();

      await expect(drawerToggle).toHaveText(nameB, { timeout: 10000 });
      await expect(adminPage.locator('[data-drawer-panel]')).toHaveCount(0);
    } finally {
      await deleteCategory(adminPage, codeA);
      await deleteCategory(adminPage, codeB);
    }
  });

  test('resets the parent field to root level and closes the drawer', async ({ adminPage }) => {
    const uid = generateUid();
    const codeA = `catppc_${uid}`;
    const codeB = `catppd_${uid}`;
    const nameB = `Picker D ${uid}`;

    try {
      const idA = await createCategory(adminPage, codeA, `Picker C ${uid}`);
      await createCategory(adminPage, codeB, nameB);

      await adminPage.goto(`/admin/catalog/categories?category=${idA}`, { waitUntil: 'networkidle' });

      const drawerToggle = parentDrawerToggle(adminPage);

      await drawerToggle.click();

      const drawerPanel = adminPage.locator('[data-drawer-panel]');

      const searched = adminPage.waitForResponse((r) => {
        if (! r.url().includes('/catalog/categories/search')) {
          return false;
        }

        return new URL(r.url()).searchParams.get('query') === codeB;
      });
      await drawerPanel.getByPlaceholder('Search categories').fill(codeB);
      await searched;
      await drawerPanel.getByText(nameB, { exact: true }).first().click();

      await expect(drawerToggle).toHaveText(nameB, { timeout: 10000 });

      await drawerToggle.click();

      const rootOption = adminPage.locator('input[type="radio"][name="parent_id_picker"][value=""]');
      await rootOption.waitFor({ state: 'attached' });
      await rootOption.dispatchEvent('change');

      await expect(drawerToggle).toHaveText('Root level', { timeout: 5000 });
      await expect(adminPage.locator('[data-drawer-panel]')).toHaveCount(0);
    } finally {
      await deleteCategory(adminPage, codeA);
      await deleteCategory(adminPage, codeB);
    }
  });
});
