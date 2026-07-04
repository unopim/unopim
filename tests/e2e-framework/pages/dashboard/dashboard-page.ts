import { expect, Page } from '@playwright/test';
import { environment } from '../../config/environment';
import { BasePage } from '../base-page';

export class DashboardPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  async open(): Promise<void> {
    await this.goto(`${environment.adminPath}/dashboard`);
  }

  async expectStatsVisible(): Promise<void> {
    await expect(this.page.locator('body')).toContainText(/dashboard|catalog|product|completeness/i);
  }
}
