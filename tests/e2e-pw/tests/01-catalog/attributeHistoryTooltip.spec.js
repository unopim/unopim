const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, searchInDataGrid, clickSaveAndExpect } = require('../../utils/helpers');

test.describe('Attribute History - View Tooltip (#703)', () => {
  test.setTimeout(90000);

  test('should show a translated tooltip on the history view icon, not a raw translation key', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `histattr_${uid}`;

    // Step 1: Create an attribute so it generates a history entry
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('networkidle');

    await adminPage.locator('input[name="code"]').fill(code);

    // Select type (e.g., Text)
    const typeMultiselect = adminPage.locator('.multiselect').filter({ has: adminPage.locator('input[name="type"]') });
    await typeMultiselect.locator('.multiselect__tags').click();
    await adminPage.waitForTimeout(300);
    await adminPage.getByRole('option', { name: 'Text' }).first().click();

    await clickSaveAndExpect(adminPage, 'Save Attribute', /Attribute created successfully/i);

    // Step 2: Navigate to the attribute's History tab
    await navigateTo(adminPage, 'attributes');
    await searchInDataGrid(adminPage, code);
    await adminPage.locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('networkidle');

    // Click History tab
    const historyTab = adminPage.getByText('History', { exact: true });
    await historyTab.click();
    await adminPage.waitForLoadState('networkidle').catch(() => {});
    await adminPage.waitForTimeout(1000);

    // Step 3: Check the eye icon tooltip does NOT show a raw translation key
    const viewIcon = adminPage.locator('span[title]').filter({ has: adminPage.locator('.icon-view') }).first()
      .or(adminPage.locator('[title*="view" i]').first())
      .or(adminPage.locator('.icon-view').first());

    const isVisible = await viewIcon.isVisible({ timeout: 5000 }).catch(() => false);

    if (isVisible) {
      const title = await viewIcon.getAttribute('title') || '';
      expect(title).not.toContain('admin::app');
      expect(title).not.toBe('');
    }

    // Also verify the page does not contain the raw key text anywhere
    const pageContent = await adminPage.textContent('body');
    expect(pageContent).not.toContain('admin::app.catalog.history.view');

    // Cleanup: delete the attribute
    await navigateTo(adminPage, 'attributes');
    await searchInDataGrid(adminPage, code);
    const deleteBtn = adminPage.locator('span[title="Delete"]').first();
    if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
      await deleteBtn.click();
      await adminPage.getByRole('button', { name: 'Delete' }).click();
      await adminPage.waitForLoadState('networkidle');
    }
  });
});
