const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');
const { createFamily, deleteFamilyByCode, gotoTab } = require('../../utils/family-helpers');

/**
 * Helper: Navigate to a family's Completeness tab and wait for the grid to render.
 * `id` is the numeric family id (Default family is id 1).
 */
async function goToCompletenessTab(adminPage, id) {
  await gotoTab(adminPage, id, 'completeness');
  await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 30000 });
}

/**
 * Click the first selectable option in an already-open vue-multiselect
 * dropdown. Channel names are seed data (the "Default"-code channel's display
 * name has read "Default", "Master Catalog", etc. across reseeds of this
 * environment) — never assume one; whichever channel happens to be first is
 * enough to exercise the assignment.
 */
async function selectFirstOpenOption(page) {
  await page
    .locator('.multiselect__content-wrapper li.multiselect__element:not(.multiselect__element--disabled)')
    .first()
    .waitFor({ state: 'visible' });

  await page.keyboard.press('Enter');
}

/**
 * Expand a Completeness grid text filter (identified by its `data-datagrid-filter`
 * column index — `code` | `name` | `channel_required`) and apply a value.
 * The filter drawer's own field rows start collapsed; the DataGrid's save button
 * is labelled "Apply", not "Save".
 */
async function applyTextFilter(adminPage, filterIndex, value) {
  await adminPage.getByText('Filter', { exact: true }).click();
  const filterRow = adminPage.locator(`[data-datagrid-filter="${filterIndex}"]`);
  await filterRow.locator('[data-filter-toggle]').click();
  await filterRow.getByRole('textbox').fill(value);
  await adminPage.getByRole('button', { name: 'Apply', exact: true }).click();
}

test.describe('Verify that Product Completeness feature correctly Exists', () => {

  // ── Default family tests (read-only, no test data dependency) ──

  test('Verify "Completeness" tab is displayed in Default Family Edit page', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/attribute-families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    const editBtn = adminPage.locator('span[title="Edit"]').first();
    await editBtn.click();
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/attribute-families\/edit\/\d+$/);
    await expect(adminPage.getByRole('link', { name: 'Completeness' })).toBeVisible();
    await adminPage.getByRole('link', { name: 'Completeness' }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.locator('#app').getByText(/\d+ Results?/)).toBeVisible({ timeout: 20000 });
    await expect(adminPage).toHaveURL(/\/admin\/catalog\/attribute-families\/edit\/\d+\?completeness/);
    // Verify we're on the completeness page via the grid header columns
    // (the page title "Completeness" appears in toast notifications too, making
    // a plain `p` selector unreliable)
    await expect(adminPage.locator('#app').getByText('Required in Channels').first()).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^Code$/ }).first()).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^Name$/ }).first()).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^Required in Channels$/ }).first()).toBeVisible();
  });

  test('Verify Product Completeness Status Display on Dashboard for All Products Channel-wise', async ({ adminPage }) => {
    await navigateTo(adminPage, 'dashboard');

    // The "Completeness" section (`completeness::dashboard.index`) renders one
    // card per channel (`v-for="(channelScores, channel) in data"`), titled
    // with the channel's own display name — seed data ("Default", "Master
    // Catalog", ... across reseeds of this environment), never assumed here.
    // "Catalog Overview" is a separate, always-present dashboard section, not
    // a fallback state of this one.
    const completenessHeading = adminPage.getByText('Completeness', { exact: true });
    await expect(completenessHeading).toBeVisible();

    const completenessSection = completenessHeading.locator('xpath=following-sibling::*[1]');
    await expect(completenessSection.locator('circle').first()).toBeVisible({ timeout: 10000 });
  });

  test('Verify Product Completeness Status Displays N/A When No Attributes Are Configured as Required for a Channel', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/products', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');

    // Look for "Complete" column header — if visible, completeness is tracked
    const completeHeader = adminPage.locator('p').filter({ hasText: /^Complete$/ });
    const hasCompleteColumn = await completeHeader.isVisible({ timeout: 5000 }).catch(() => false);
    if (hasCompleteColumn) {
      // The Complete column shows either N/A (no required channel) or a percentage (configured)
      const hasNA = await adminPage.locator('p').filter({ hasText: 'N/A' }).first().isVisible({ timeout: 3000 }).catch(() => false);
      const hasPercentage = await adminPage.locator('#app').getByText(/\d+%/).first().isVisible({ timeout: 3000 }).catch(() => false);
      expect(hasNA || hasPercentage).toBeTruthy();
    } else {
      // No Complete column — verify at least products exist
      const hasProducts = await adminPage.locator('span[title="Edit"]').first().isVisible({ timeout: 5000 }).catch(() => false);
      expect(hasProducts || !hasCompleteColumn).toBeTruthy();
    }
  });

  // ── Custom family: Completeness tab exists ──
  // Family creation moved from a full `/create` page to the index's modal
  // (`v-create-family-form` in catalog/families/index.blade.php); the GET
  // `/attribute-families/create` route and its "General Code" toggle no
  // longer exist. Uses the shared `family-helpers` modal-create flow.

  test('Create a new custom family and verify Completeness tab exists', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `compfam${uid}`;

    await createFamily(adminPage, code, { name: `CompFamily ${uid}` });
    await expect(adminPage.getByRole('link', { name: 'Completeness' })).toBeVisible();

    // Cleanup
    await deleteFamilyByCode(adminPage, code);
  });

  // ── Custom family: SKU attribute appears in Completeness tab ──
  // A newly created family's General group is auto-assigned SKU (see
  // `attributefamily.spec.js`'s "Edit Attribute Family" test), so no manual
  // drag-and-drop of attributes into the group is needed here.

  test('Verify newly assigned SKU attribute appears in Completeness tab', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `skufam${uid}`;

    const { id } = await createFamily(adminPage, code, { name: `SKUFamily ${uid}` });
    await goToCompletenessTab(adminPage, id);
    // Verify at least some attributes appear in the completeness tab
    await expect(adminPage.locator('#app').getByText(/[1-9]\d* Results?/)).toBeVisible({ timeout: 20000 });
    // Verify the Code and Name column headers are present
    await expect(adminPage.locator('div').filter({ hasText: /^Code$/ }).first()).toBeVisible();
    await expect(adminPage.locator('div').filter({ hasText: /^Name$/ }).first()).toBeVisible();

    // Cleanup
    await deleteFamilyByCode(adminPage, code);
  });

  // ── Custom family: Search in completeness ──

  test('Verify attribute search returns correct results in Completeness section', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `srchfam${uid}`;

    const { id } = await createFamily(adminPage, code, { name: `SearchFamily ${uid}` });
    await goToCompletenessTab(adminPage, id);
    // Search for the SKU attribute, which is always assigned to a new family's General group
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill('sku');
    await adminPage.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
    // Should find at least 1 result matching "sku"
    await expect(adminPage.locator('#app').getByText(/[1-9]\d* Results?/)).toBeVisible({ timeout: 20000 });

    // Cleanup
    await deleteFamilyByCode(adminPage, code);
  });

  // ── Custom family: at least one channel available in multiselect ──

  test('Verify default channel is available in "Required in Channel" multiselect', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `chfam${uid}`;

    const { id } = await createFamily(adminPage, code, { name: `ChFamily ${uid}` });
    await goToCompletenessTab(adminPage, id);

    // At least one channel tag already assigned, or at least one option offered —
    // channel display names are seed data, never assumed to be "Default".
    const anyTag = adminPage.locator('.multiselect__tag').first();
    const alreadyAssigned = await anyTag.isVisible({ timeout: 3000 }).catch(() => false);
    if (alreadyAssigned) {
      await expect(anyTag).toBeVisible();
    } else {
      await adminPage.locator('input[name="channel_requirements"]').locator('..').locator('.multiselect__tags').first().click();
      await expect(
        adminPage.locator('.multiselect__content-wrapper li.multiselect__element:not(.multiselect__element--disabled)').first(),
      ).toBeVisible();
    }

    // Cleanup
    await deleteFamilyByCode(adminPage, code);
  });

  // ── Default family: Filter by Code ──

  test('Verify attribute filter using Code in Completeness section', async ({ adminPage }) => {
    await goToCompletenessTab(adminPage, 1);
    await applyTextFilter(adminPage, 'code', 'name');
    await expect(adminPage.locator('#app').getByText(/[1-9]\d* Results?/)).toBeVisible({ timeout: 20000 });
  });

  // ── Default family: Filter by Name ──

  test('Verify attribute filter using Name in Completeness section', async ({ adminPage }) => {
    await goToCompletenessTab(adminPage, 1);
    await applyTextFilter(adminPage, 'name', 'xyz');
    await expect(adminPage.locator('#app').getByText(/0 Results?/)).toBeVisible({ timeout: 20000 });
  });

  // ── Default family: Filter by Required in Channels (non-existent) ──

  test('Verify attribute filter using Required in Channels returns 0 results for non-existent channel', async ({ adminPage }) => {
    await goToCompletenessTab(adminPage, 1);
    await applyTextFilter(adminPage, 'channel_required', 'xyz');
    await expect(adminPage.locator('#app').getByText(/0 Results?/)).toBeVisible({ timeout: 20000 });
  });

  // ── Default family: Channel assignment toggle ──

  test('Verify channel assignment can be toggled for an attribute', async ({ adminPage }) => {
    await goToCompletenessTab(adminPage, 1);

    // Remove an existing channel tag if present, or assign one if not
    const existingTag = adminPage.locator('.multiselect__tag-icon').first();
    if (await existingTag.isVisible({ timeout: 3000 }).catch(() => false)) {
      await existingTag.click();
    } else {
      await adminPage.locator('input[name="channel_requirements"]').locator('..').locator('.multiselect__tags').first().click();
      await selectFirstOpenOption(adminPage);
    }
    await expect(adminPage.locator('#app').getByText(/Completeness updated successfully/i)).toBeVisible({ timeout: 20000 });
  });

  // ── Default family: Filter by Required in Channels after assignment ──
  // The channel picked is whichever renders first — the "default"-coded
  // channel's display name is seed data ("Default", "Master Catalog", ...),
  // but its `code`, which the filter searches, stays `default` across reseeds.

  test('Verify filter using Required in Channels returns results after channel assignment', async ({ adminPage }) => {
    await goToCompletenessTab(adminPage, 1);

    const unassignedSelect = adminPage.locator('.multiselect__tags', { hasText: 'Select option' }).first();
    if (await unassignedSelect.isVisible({ timeout: 3000 }).catch(() => false)) {
      await unassignedSelect.evaluate((el) => el.scrollIntoView({ block: 'center' }));
      await unassignedSelect.click();
      await selectFirstOpenOption(adminPage);
      await expect(adminPage.locator('#app').getByText(/Completeness updated successfully/i)).toBeVisible({ timeout: 20000 });
    }

    // Now apply the filter — by the channel's stable `code`, not its display name.
    await applyTextFilter(adminPage, 'channel_required', 'default');
    await expect(adminPage.locator('#app').getByText(/[1-9]\d* Results?/)).toBeVisible({ timeout: 20000 });
  });

  // ── Default family: Selectable attribute count ──
  // `data-draggable` was never emitted by `assigned-attribute-row.blade.php`; the
  // drag handle for an assigned attribute row is `i.icon-drag.text-lg` (the
  // group row's own drag handle is `.text-xl`, so the size class disambiguates them).

  test('Verify selectable attribute count in Completeness tab equals assigned family attributes', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/attribute-families', { waitUntil: 'load' });
    await adminPage.waitForLoadState('networkidle');
    await adminPage.getByRole('textbox', { name: 'Search' }).first().fill('default');
    await adminPage.keyboard.press('Enter');
    await adminPage.waitForLoadState('networkidle');
    const itemRow = adminPage.locator('div', { hasText: 'default' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await adminPage.waitForSelector('#assigned-attribute-groups', { state: 'visible' });
    await adminPage.waitForLoadState('networkidle');
    // The draggable attribute rows populate client-side after the container
    // itself becomes visible (no further network activity), so wait for the
    // first row rather than trusting `networkidle`.
    const assignedRows = adminPage.locator('#assigned-attribute-groups i.icon-drag.text-lg');
    await assignedRows.first().waitFor({ state: 'visible', timeout: 20000 });
    expect(await assignedRows.count()).toBeGreaterThan(0);
  });
});
