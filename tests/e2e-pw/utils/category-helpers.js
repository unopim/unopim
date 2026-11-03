const { navigateTo, clickSaveAndExpect } = require('./helpers');

/**
 * Helper: Open the Create Category form and let the unsaved-changes tracker
 * snapshot the pristine form. The redesigned forms save via the global
 * "Save changes" bar, which only appears once a field actually changes — so
 * fields must be edited AFTER the initial snapshot settles.
 */
async function openCreateForm(adminPage) {
  await navigateTo(adminPage, 'categories');
  await adminPage.getByRole('link', { name: 'Add Category' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.locator('input[name="code"]').waitFor({ state: 'visible' });
  await adminPage.waitForTimeout(1200);
}

/**
 * Helper: Create a category via UI.
 */
async function createCategory(adminPage, code, name) {
  await openCreateForm(adminPage);
  await adminPage.locator('input[name="code"]').fill(code);
  await adminPage.locator('#name').fill(name);
  await clickSaveAndExpect(adminPage, 'Save changes', /category created successfully/i);
}

/**
 * Helper: Delete a category by code.
 */
async function deleteCategory(adminPage, code) {
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
}

module.exports = {
  openCreateForm,
  createCategory,
  deleteCategory,
};
