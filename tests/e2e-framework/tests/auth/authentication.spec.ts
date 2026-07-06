import { test, expect } from '../../fixtures/base-test';
import { environment } from '../../config/environment';

test.describe('Authentication', () => {
  test.use({ storageState: { cookies: [], origins: [] } });

  test('@smoke admin can log in with valid credentials', async ({ loginPage, page }) => {
    await loginPage.login();
    // A successful login lands on the first ACL-allowed admin page and must
    // leave the login route. `/admin/login` also contains "admin", so a bare
    // /admin/ match would pass on a failed login — assert we left /login.
    await expect(page).not.toHaveURL(/\/login(\?|#|$)/);
    await expect(page).toHaveURL(/\/admin\//);
  });

  test('@negative invalid credentials are rejected', async ({ loginPage }) => {
    await loginPage.open();
    await loginPage.email.fill(environment.adminEmail);
    await loginPage.password.fill('invalid-password');
    await loginPage.submit.click();
    await loginPage.expectInvalidCredentialsError();
  });

  test('@security login form rejects empty mandatory fields', async ({ loginPage }) => {
    await loginPage.open();
    await loginPage.submit.click();
    await loginPage.expectFieldValidationErrors();
  });
});
