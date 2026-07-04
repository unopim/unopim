import { expect, Locator, Page } from '@playwright/test';

export abstract class BasePage {
  protected constructor(protected readonly page: Page) {}

  async goto(path: string): Promise<void> {
    await this.page.goto(path);
    await this.waitForAppReady();
  }

  async waitForAppReady(): Promise<void> {
    await this.page.waitForLoadState('domcontentloaded');
    await this.page.waitForLoadState('networkidle').catch(() => undefined);
  }

  toast(): Locator {
    return this.page.locator('[role="alert"], .alert, .flash-message, .notification').last();
  }

  primaryHeading(): Locator {
    return this.page.locator('h1, [data-test="page-title"], .page-title').first();
  }

  async expectLoaded(): Promise<void> {
    await expect(this.page).not.toHaveURL(/login$/);
    await expect(this.page.locator('body')).toBeVisible();
  }

  async expectNoCriticalConsoleErrors(): Promise<void> {
    const errors: string[] = [];
    this.page.on('console', (message) => {
      if (message.type() === 'error') {
        errors.push(message.text());
      }
    });
    await this.waitForAppReady();
    expect(errors, errors.join('\n')).toHaveLength(0);
  }
}
