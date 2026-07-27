import { chromium } from '@playwright/test';
import { login } from './utils/login.js';
import fs from 'fs';

process.env.BASE_URL = 'http://192.168.15.243:8023';

const browser = await chromium.launch();
const context = await browser.newContext({ viewport: { width: 1280, height: 800 } });
const page = await context.newPage();
page.on('response', async r => {
  if (r.url().includes('catalog/products') && r.request().method()==='GET') {
    console.log('RESP', r.status(), r.url());
  }
});

await login(page);
await page.goto('http://192.168.15.243:8023/admin/catalog/products', { waitUntil: 'domcontentloaded' });
await page.waitForTimeout(8000);
console.log('URL:', page.url());
await page.screenshot({ path: '/tmp/dbg-grid2.png', fullPage: true });
await browser.close();
