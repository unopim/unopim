const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid, clickSaveAndExpect, searchInDataGrid } = require('../../utils/helpers');

/**
 * Helper: Create an attribute family via UI.
 */
async function createFamily(adminPage, code, name) {
  await navigateTo(adminPage, 'attributeFamilies');
  await adminPage.getByRole('link', { name: 'Create Attribute Family' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Enter Code' }).fill(code);
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill(name);
  await clickSaveAndExpect(adminPage, 'Save Attribute Family', /Family created successfully/i);
}

/**
 * Helper: Delete a family by code.
 */
async function deleteFamily(adminPage, code) {
  await navigateTo(adminPage, 'attributeFamilies');
  await searchInDataGrid(adminPage, code);
  const deleteBtn = adminPage.locator('div', { hasText: code }).locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
}

test.describe('Attribute Family - Save with empty groups (#709)', () => {
  test.setTimeout(90000);

  test('should not crash when saving attribute family after removing all assigned groups', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `emptygrp_${uid}`;

    // Step 1: Create an attribute family
    await createFamily(adminPage, code, 'Empty Group Test');

    // Step 2: Edit it — assign a group
    await navigateTo(adminPage, 'attributeFamilies');
    await searchInDataGrid(adminPage, code);
    await adminPage.locator('div', { hasText: code }).locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('networkidle');

    // Assign an attribute group
    await adminPage.locator('.secondary-button', { hasText: 'Assign Attribute Group' }).click();
    await adminPage.locator('input[name="group"]').locator('..').locator('.multiselect__placeholder').click();
    // Pick the first available group
    const firstGroupOption = adminPage.getByRole('option').first();
    await firstGroupOption.waitFor({ state: 'visible', timeout: 10000 });
    await firstGroupOption.click();
    await adminPage.getByRole('button', { name: 'Assign Attribute Group' }).click();
    await adminPage.waitForTimeout(500);

    // Save with the group assigned
    await clickSaveAndExpect(adminPage, 'Save Attribute Family', /Family updated successfully/i);

    // Step 3: Edit again — delete the group
    await navigateTo(adminPage, 'attributeFamilies');
    await searchInDataGrid(adminPage, code);
    await adminPage.locator('div', { hasText: code }).locator('span[title="Edit"]').first().click();
    await adminPage.waitForLoadState('networkidle');

    // Click "Delete Group" to remove the assigned group
    await adminPage.getByText('Delete Group', { exact: true }).click();
    await adminPage.waitForTimeout(500);

    // Select the group to delete (click its checkbox or select it)
    const groupCheckbox = adminPage.locator('#assigned-attribute-groups input[type="checkbox"]').first();
    if (await groupCheckbox.isVisible({ timeout: 3000 }).catch(() => false)) {
      await groupCheckbox.click();
      // Confirm deletion
      const confirmBtn = adminPage.getByRole('button', { name: /Delete/i }).last();
      await confirmBtn.click();
      await adminPage.waitForTimeout(500);
    }

    // Step 4: Save — should NOT show a 500 error page
    await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
    await adminPage.waitForLoadState('networkidle').catch(() => {});

    // Verify no error page (no "Undefined variable", no 500)
    const pageContent = await adminPage.textContent('body');
    expect(pageContent).not.toContain('Undefined variable');
    expect(pageContent).not.toContain('ErrorException');
    expect(pageContent).not.toContain('500 Internal Server Error');

    // Cleanup
    await deleteFamily(adminPage, code);
  });
});
