import { test, expect } from '@playwright/test';
import { login } from '../helpers/login';

test.use({
    browserName: 'firefox',
    storageState: 'storage/auth.json', // Load session state
});

test.describe('UnoPim Authenticated Tests', () => {
    test('should navigate to dashboard without login', async ({ page }) => {
        await page.goto('/admin/dashboard'); // Directly go to dashboard
        await expect(page).toHaveURL('http://localhost:8000/admin/dashboard');
    });
});
