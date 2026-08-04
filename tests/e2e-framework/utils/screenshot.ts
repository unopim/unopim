import path from 'node:path';
import fs from 'node:fs/promises';
import type { Page } from '@playwright/test';

export class ScreenshotUtility {
  constructor(private readonly page: Page) {}

  async capture(name: string): Promise<Buffer> {
    const destination = path.resolve('reports/screenshots', `${name}.png`);
    await fs.mkdir(path.dirname(destination), { recursive: true });
    return this.page.screenshot({ path: destination, fullPage: true });
  }
}
