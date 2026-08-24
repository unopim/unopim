import { test as base, expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { ApiClient } from '../api/api-client';
import { DatabaseHelper } from '../database/database-helper';
import { LoginPage } from '../pages/auth/login-page';
import { CrudPage } from '../pages/shared/crud-page';
import { RandomData } from '../utils/random-data';
import { AuthHelper } from '../helpers/auth-helper';
import { NetworkUtility } from '../utils/network';
import { VisualRegressionUtility } from '../utils/visual-regression';
import { AccessibilityUtility } from '../utils/accessibility';

type UnoPimFixtures = {
  api: ApiClient;
  db: DatabaseHelper;
  loginPage: LoginPage;
  crudPage: CrudPage;
  randomData: RandomData;
  auth: AuthHelper;
  network: NetworkUtility;
  visual: VisualRegressionUtility;
  a11y: AccessibilityUtility;
};

export const test = base.extend<UnoPimFixtures>({
  api: async ({ request }, use) => {
    await use(new ApiClient(request));
  },
  db: async ({ }, use) => {
    const db = new DatabaseHelper();
    await use(db);
    await db.close();
  },
  loginPage: async ({ page }, use) => {
    await use(new LoginPage(page));
  },
  crudPage: async ({ page }, use) => {
    await use(new CrudPage(page));
  },
  randomData: async ({ }, use) => {
    await use(new RandomData());
  },
  auth: async ({ page }, use) => {
    await use(new AuthHelper(page));
  },
  network: async ({ page }, use) => {
    await use(new NetworkUtility(page));
  },
  visual: async ({ page }, use) => {
    await use(new VisualRegressionUtility(page));
  },
  a11y: async ({ page }, use) => {
    await use(new AccessibilityUtility(page));
  }
});

test.beforeEach(async ({ page }, testInfo) => {
  await page.setViewportSize({ width: 1440, height: 900 });
  page.setDefaultTimeout(testInfo.timeout);
});

export { expect };
export type { Page };
