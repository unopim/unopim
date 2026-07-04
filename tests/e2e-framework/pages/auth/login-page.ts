import { expect, Page } from '@playwright/test';
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
    await this.submit.click();

    await Promise.race([
      this.page.waitForURL((url) => !url.pathname.endsWith('/login'), { timeout: 30_000 }),
      this.page.waitForSelector('body', { timeout: 30_000 })
    ]).catch(() => undefined);

    await this.page.waitForLoadState('domcontentloaded').catch(() => undefined);
    await this.page.waitForTimeout(2000);
  }

  async expectValidationError(): Promise<void> {
    await expect(this.page.locator('body')).toContainText(/required|invalid|credentials|password/i);
  }
}
