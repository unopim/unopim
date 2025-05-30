import { test, expect } from '@playwright/test';
test.describe('UnoPim Test cases', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();
  });

test('Create Integration with empty Name field', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    await page.getByRole('link', { name: 'Create' }).click();
    await page.getByRole('textbox', { name: 'Name' }).click();
    await page.getByRole('textbox', { name: 'Name' }).fill('');
    await page.locator('input[name="admin_id"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'John Doe' }).locator('span').first().click();
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('The Name field is required')).toBeVisible();
});
test('Create Integration field with empty Assign User field', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    await page.getByRole('link', { name: 'Create' }).click();
    await page.getByRole('textbox', { name: 'Name' }).click();
    await page.getByRole('textbox', { name: 'Name' }).fill('Admin User');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('The Assign User field is required')).toBeVisible();
});

test('Create Integration field with empty Name and Assign User field', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    await page.getByRole('link', { name: 'Create' }).click();
    await page.getByRole('textbox', { name: 'Name' }).click();
    await page.getByRole('textbox', { name: 'Name' }).fill('');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText('The Name field is required')).toBeVisible();
    await expect(page.getByText('The Assign User field is required')).toBeVisible();
});

test('Create Integration field', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    await page.getByRole('link', { name: 'Create' }).click();
    await page.getByRole('textbox', { name: 'Name' }).click();
    await page.getByRole('textbox', { name: 'Name' }).fill('Admin User');
    await page.locator('input[name="admin_id"]').locator('..').locator('.multiselect__placeholder').click();
    await page.getByRole('option', { name: 'John Doe' }).locator('span').first().click();
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText(/API Integration Created Successfully/i)).toBeVisible();
});

test('should allow Integration search', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    await page.getByRole('textbox', { name: 'Search' }).click();
    await page.getByRole('textbox', { name: 'Search' }).type('Admin User');
    await page.keyboard.press('Enter');
    await expect(page.locator('text=Admin User')).toBeVisible();
  });


  test('should open the filter menu when clicked', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    await page.getByText('Filter', { exact: true }).click();
    await expect(page.getByText('Apply Filters')).toBeVisible();
  });

   test('should allow setting items per page', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    await page.getByRole('button', { name: '' }).click();
    await page.getByText('20', { exact: true }).click();
    await expect(page.getByRole('button', { name: '' })).toContainText('20');
  });

  test('should perform actions on a Integration (Edit, Delete)', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    const itemRow = page.locator('div', { hasText: 'Admin User' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(page).toHaveURL(/\/admin\/integrations\/api-keys\/edit/);
     await page.goBack();
    await itemRow.locator('span[title="Delete"]').first().click();
    await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
  });

test('Generate API key', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    const itemRow = page.locator('div', { hasText: 'Admin USer' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.getByRole('button', { name: 'Generate' }).click();
    await expect(page.getByText(/API key is generated successfully/i)).toBeVisible();
    const clientIdInput = page.locator('#client_id');
    await expect(clientIdInput).not.toHaveValue('');
    const secretkeyInput = page.locator('#secret_key');
    await expect(secretkeyInput).not.toHaveValue('');
});


test('Regenerate API key', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    const itemRow = page.locator('div', { hasText: 'Admin USer' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.getByRole('button', { name: 'Re-Generate Secret Key' }).click();
    await expect(page.getByText(/API secret key is regenerated successfully/i)).toBeVisible();
    const clientIdInput = page.locator('#client_id');
    await expect(clientIdInput).not.toHaveValue('');
    const secretkeyInput = page.locator('#secret_key');
    await expect(secretkeyInput).not.toHaveValue('');
});

test('Update Integration', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    const itemRow = page.locator('div', { hasText: 'Admin USer' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await page.getByRole('textbox', { name: 'Name' }).click();
    await page.getByRole('textbox', { name: 'Name' }).fill('Admin Testing');
    await page.getByRole('button', { name: 'Save' }).click();
    await expect(page.getByText(/API Integration is updated successfully/i)).toBeVisible();
});


test('Delete Integration', async ({ page }) => {
    await page.getByRole('link', { name: ' Configuration' }).click();
    await page.getByRole('link', { name: 'Integrations' }).click();
    const itemRow = page.locator('div', { hasText: 'Admin Testing' });
    await itemRow.locator('span[title="Delete"]').first().click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await expect(page.getByText(/API Integration is deleted successfully/i)).toBeVisible();
  });
});


