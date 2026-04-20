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
    await adminPage.getByRole('button', { name: 'Delete', exact: true }).first().click();
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

    // Select the first assigned group so deleteGroup() has a target
    const firstGroupNode = adminPage.locator('#assigned-attribute-groups .group_node').first();
    await firstGroupNode.waitFor({ state: 'visible', timeout: 10000 });
    await firstGroupNode.click();

    // Open the delete-group confirm modal
    await adminPage.getByText('Delete Group', { exact: true }).click();

    // Confirm in the modal (default agree label is "Agree")
    const agreeBtn = adminPage.getByRole('button', { name: 'Agree', exact: true });
    await agreeBtn.waitFor({ state: 'visible', timeout: 10000 });
    await agreeBtn.click();

    // Wait for the modal's Agree button to disappear before saving
    await agreeBtn.waitFor({ state: 'hidden', timeout: 10000 });

    // Step 4: Save — must redirect with success flash, not a 500 error
    await clickSaveAndExpect(adminPage, 'Save Attribute Family', /Family updated successfully/i);

    // Cleanup
    await deleteFamily(adminPage, code);
  });
});
