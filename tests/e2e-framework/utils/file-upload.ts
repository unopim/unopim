import path from 'node:path';
import { Page } from '@playwright/test';

export class FileUploadUtility {
  constructor(private readonly page: Page) {}

  async uploadByInput(selector: string, relativeFilePath: string): Promise<void> {
    await this.page.setInputFiles(selector, path.resolve(relativeFilePath));
  }
}
