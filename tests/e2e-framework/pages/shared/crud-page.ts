import { expect, Page } from '@playwright/test';
import { BasePage } from '../base-page';

export class CrudPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  grid = this.page.locator('table, [role="table"], .datagrid').first();
  search = this.page.getByPlaceholder(/search/i).or(this.page.locator('input[type="search"]').first());
  filters = this.page.getByRole('button', { name: /filter/i }).or(this.page.locator('[data-test*="filter"]').first());
  firstCheckbox = this.page.locator('input[type="checkbox"]').first();
  createButton = this.page.getByRole('link', { name: /create|add|new/i }).or(this.page.getByRole('button', { name: /create|add|new/i }));
  saveButton = this.page.getByRole('button', { name: /save/i });
  deleteButton = this.page.getByRole('button', { name: /delete/i }).or(this.page.getByRole('link', { name: /delete/i }));

  async expectIndexReady(): Promise<void> {
    await this.expectLoaded();
    await expect(this.page.locator('body')).not.toContainText(/server error|exception/i);
  }

  async openCreateIfAvailable(): Promise<boolean> {
    if (await this.createButton.first().isVisible().catch(() => false)) {
      await this.createButton.first().click();
      await this.waitForAppReady();
      return true;
    }
    return false;
  }

  async assertSearchDoesNotCrash(term: string): Promise<void> {
    if (await this.search.isVisible().catch(() => false)) {
      await this.search.fill(term);
      await this.page.keyboard.press('Enter');
      await this.waitForAppReady();
      await expect(this.page.locator('body')).not.toContainText(/exception|sql syntax|stack trace/i);
    }
  }

  async assertKeyboardTabOrder(): Promise<void> {
    await this.page.keyboard.press('Tab');
    await expect(this.page.locator(':focus')).toBeVisible();
  }
}
