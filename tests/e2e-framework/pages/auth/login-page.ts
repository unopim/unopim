import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { environment } from '../../config/environment';
import { BasePage } from '../base-page';

export class LoginPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  readonly email = this.page.getByLabel(/email/i).or(this.page.locator('input[name="email"]'));
  readonly password = this.page.getByLabel(/password/i).or(this.page.locator('input[name="password"]'));
  readonly submit = this.page.getByRole('button', { name: /sign in|login/i }).or(this.page.locator('button[type="submit"]'));

  async open(): Promise<void> {
    await this.goto(`${environment.adminPath}/login`);
  }

  async login(email = environment.adminEmail, password = environment.adminPassword): Promise<void> {
    await this.open();
    await this.email.fill(email);
    await this.password.fill(password);

    await this.password.press('Enter');

    await Promise.race([
      this.page.waitForURL((url) => /\/admin\//.test(url.toString()) && !url.pathname.endsWith('/login'), { timeout: 30_000 }),
      this.page.waitForSelector('body', { timeout: 30_000 })
    ]).catch(() => undefined);

    await this.page.waitForLoadState('domcontentloaded').catch(() => undefined);
    await this.page.waitForTimeout(1500);
  }

  /**
   * Server-side rejection after a well-formed but wrong login attempt.
   * UnoPim flashes "Please check your credentials and try again." and
   * redirects back to the login route.
   */
  async expectInvalidCredentialsError(): Promise<void> {
    await expect(this.page.getByText(/check your credentials/i)).toBeVisible();
    await expect(this.page).toHaveURL(/\/login/);
  }

  /**
   * Client-side VeeValidate errors shown when mandatory fields are empty.
   * These render as `<p class="text-red-600">` next to each control, so we
   * assert on the error element rather than the whole page (which always
   * contains the word "password" via the field label).
   */
  async expectFieldValidationErrors(): Promise<void> {
    await expect(this.page.locator('.text-red-600').first()).toBeVisible();
  }
}
