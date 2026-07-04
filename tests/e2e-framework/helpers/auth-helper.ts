import { Page } from '@playwright/test';
import { environment } from '../config/environment';
import { LoginPage } from '../pages/auth/login-page';

export class AuthHelper {
  constructor(private readonly page: Page) {}

  async loginAsAdmin(): Promise<void> {
    await new LoginPage(this.page).login(environment.adminEmail, environment.adminPassword);
  }

  async logout(): Promise<void> {
    await this.page.request.delete(`${environment.adminPath}/logout`);
    await this.page.goto(`${environment.adminPath}/login`);
  }
}
