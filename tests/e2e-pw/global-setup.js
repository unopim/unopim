import { chromium } from '@playwright/test';
import { login } from './utils/login.js';
import fs from 'fs';
import path from 'path';


const STORAGE_PATH = path.resolve('.state/admin-auth.json');

export default async function globalSetup() {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  await login(page);
  await page.context().storageState({ path: STORAGE_PATH });
  await browser.close();
}
