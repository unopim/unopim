import { test, expect } from '../../fixtures/base-test';
import { environment } from '../../config/environment';

test.describe('Authentication', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('@smoke admin can log in with valid credentials', async ({ loginPage, page }) => {
    await loginPage.login();
    await expect(page).toHaveURL(/dashboard|admin/);
  });

  test('@negative invalid credentials are rejected', async ({ loginPage }) => {
    await loginPage.open();
    await loginPage.email.fill(environment.adminEmail);
    await loginPage.password.fill('invalid-password');
    await loginPage.submit.click();
    await loginPage.expectValidationError();
  });

  test('@security login form rejects empty mandatory fields', async ({ loginPage }) => {
    await loginPage.open();
    await loginPage.submit.click();
    await loginPage.expectValidationError();
  });
});
