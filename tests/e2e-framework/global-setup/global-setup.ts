import fs from 'node:fs/promises';
import path from 'node:path';
import { chromium } from '@playwright/test';
import type { FullConfig } from '@playwright/test';
import { environment } from '../config/environment';
import { LoginPage } from '../pages/auth/login-page';

export default async function globalSetup(_config: FullConfig): Promise<void> {
  await fs.mkdir(path.dirname(environment.storageStatePath), { recursive: true });

  const browser = await chromium.launch({ channel: 'chrome' });
  const page = await browser.newPage({ baseURL: environment.baseUrl });
  const loginUrl = `${environment.adminPath}/login`;

  await page.goto(loginUrl, { waitUntil: 'domcontentloaded' });
  await page.getByRole('textbox', { name: /email/i }).waitFor({ state: 'visible', timeout: 15_000 }).catch(() => undefined);

  await new LoginPage(page).login();
  await page.context().storageState({ path: environment.storageStatePath });
  await browser.close();
}
