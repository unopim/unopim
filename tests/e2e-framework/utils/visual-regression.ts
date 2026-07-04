import { expect, Page } from '@playwright/test';

export class VisualRegressionUtility {
  constructor(private readonly page: Page) {}

  async expectPageSnapshot(name: string): Promise<void> {
    await expect(this.page).toHaveScreenshot(`${name}.png`, {
      fullPage: true,
      animations: 'disabled',
      maxDiffPixelRatio: 0.02
    });
  }
}
