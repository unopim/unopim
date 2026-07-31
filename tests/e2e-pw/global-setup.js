import { chromium } from '@playwright/test';
import { login } from './utils/login.js';
import fs from 'fs';
import path from 'path';


const STORAGE_PATH = path.resolve('.state/admin-auth.json');

export default async function globalSetup() {
  const browser = await chromium.launch();
  const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });
  await login(page);

  // Dismiss the marketing promo banner once for the shared test admin. It renders
  // as a `sticky top-0 z-[9999]` bar that shifts the admin layout, which pushes the
  // sidebar/menu and dashboard widgets and breaks the fly-out and dashboard specs.
  // Dismissal is persisted per-admin, so clearing it here applies to every test
  // that reuses this storage state.
  for (let attempt = 0; attempt < 5; attempt++) {
    const dismiss = page.locator('#unopim-promo-bar button:visible').last();

    if (!(await dismiss.isVisible().catch(() => false))) {
      break;
    }

    await dismiss.click().catch(() => {});
    await page.waitForTimeout(300);
  }

  await page.context().storageState({ path: STORAGE_PATH });
  await browser.close();

  /*
   * Bootstrap the API integration once, before workers spawn. Left to the
   * workers, each one that finds no .api-config.json creates or rotates the
   * client secret concurrently — every worker holding a losing secret then
   * fails token requests with invalid_client.
   */
  try {
    const { ensureApiCredentials } = await import('./utils/api-credential-setup.js');

    await ensureApiCredentials({
      baseUrl: (process.env.API_BASE_URL || process.env.BASE_URL || 'http://127.0.0.1:8000').replace(/\/$/, ''),
      adminEmail: process.env.ADMIN_EMAIL || process.env.ADMIN_USERNAME,
      adminPassword: process.env.ADMIN_PASSWORD,
      integrationName: process.env.API_INTEGRATION_NAME,
    });
  } catch (error) {
    console.warn('[global-setup] API credential bootstrap skipped:', error.message);
  }
}
