import { chromium } from '@playwright/test';
import fs from 'fs';

async function globalSetup() {
    const browser = await chromium.launch(); // Use chromium, Playwright handles cross-browser storage
    const context = await browser.newContext();
    const page = await context.newPage();

    // Perform login
    await page.goto('http://localhost:8000');
    await page.fill('input[name="email"]', 'admin@example.com'); // Replace with actual credentials
    await page.fill('input[name="password"]', 'admin123');
    await page.click('.primary-button');

    // Wait for successful login (Adjust selector based on actual dashboard page)
    await page.waitForURL('http://localhost:8000/admin/dashboard');

    // Save authentication state
    await context.storageState({ path: 'storage/auth.json' });

    // Ensure the storage file exists
    if (!fs.existsSync('storage/auth.json')) {
        throw new Error('Auth storage file was not created.');
    }

    // await browser.close();
}

export default globalSetup;
