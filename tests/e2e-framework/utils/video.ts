import fs from 'node:fs/promises';
import path from 'node:path';
import type { Page } from '@playwright/test';

export class VideoUtility {
  constructor(private readonly page: Page) {}

  async saveAs(name: string): Promise<string | undefined> {
    const video = this.page.video();
    if (!video) {
      return undefined;
    }

    const destination = path.resolve('reports/videos', `${name}.webm`);
    await fs.mkdir(path.dirname(destination), { recursive: true });
    await video.saveAs(destination);
    return destination;
  }
}
