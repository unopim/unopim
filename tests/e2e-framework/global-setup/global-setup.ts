import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium, FullConfig } from '@playwright/test';
import { environment } from '../config/environment';
import { LoginPage } from '../pages/auth/login-page';

export default async function globalSetup(_config: FullConfig): Promise<void> {
  await fs.mkdir(path.dirname(environment.storageStatePath), { recursive: true });

  const browser = await chromium.launch({ channel: 'chrome' });
  const page = await browser.newPage({ baseURL: environment.baseUrl });
  await new LoginPage(page).login();
  await page.context().storageState({ path: environment.storageStatePath });
  await browser.close();
}
