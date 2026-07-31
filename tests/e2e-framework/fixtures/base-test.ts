import { test as base, expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { ApiClient } from '../api/api-client';
import { DatabaseHelper } from '../database/database-helper';
import { LoginPage } from '../pages/auth/login-page';
import { CrudPage } from '../pages/shared/crud-page';
import { RandomData } from '../utils/random-data';

type UnoPimFixtures = {
  api: ApiClient;
  db: DatabaseHelper;
  loginPage: LoginPage;
  crudPage: CrudPage;
  randomData: RandomData;
};

export const test = base.extend<UnoPimFixtures>({
  api: async ({ request }, use) => {
    await use(new ApiClient(request));
  },
  db: async ({}, use) => {
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
  randomData: async ({}, use) => {
    await use(new RandomData());
  }
});

export { expect };
export type { Page };
