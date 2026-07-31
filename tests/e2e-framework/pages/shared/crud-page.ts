import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { BasePage } from '../base-page';

export class CrudPage extends BasePage {
  constructor(page: Page) {
    super(page);
  }

  grid = this.page.getByRole('table').first();
  search = this.page.getByPlaceholder(/search/i).first();
  filters = this.page.getByRole('button', { name: /filter/i }).first();
  firstCheckbox = this.page.getByRole('checkbox').first();
  createButton = this.page.getByRole('link', { name: /create|add|new/i }).first();
  saveButton = this.page.getByRole('button', { name: /save/i }).first();
  deleteButton = this.page.getByRole('button', { name: /delete/i }).first();

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
