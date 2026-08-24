import type { Page } from '@playwright/test';
import { environment } from '../config/environment';
import { LoginPage } from '../pages/auth/login-page';

export class AuthHelper {
  constructor(private readonly page: Page) { }

  async loginAsAdmin(): Promise<void> {
    await new LoginPage(this.page).login(environment.adminEmail, environment.adminPassword);
  }

  async logout(): Promise<void> {
    // Use evaluate to send a DELETE request with fetch (same session/cookies as the page)
    await this.page.evaluate(async (logoutUrl: string) => {
      await fetch(logoutUrl, { method: 'DELETE', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    }, `${environment.adminPath}/logout`);
    await this.page.goto(`${environment.adminPath}/login`);
  }
}

