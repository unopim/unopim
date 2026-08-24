import { test, expect } from '../../fixtures/base-test';
import { modules } from '../../constants/modules';
import { environment } from '../../config/environment';
import { protectedModules } from '../../constants/protectedModules';

test.describe('Authorization', () => {
  for (const module of modules.filter(
    (item) =>
      item.path?.startsWith('/admin') &&
      !item.path.includes('{') &&
      !protectedModules.has(item.key)
  )) {
    test(`@authorization unauthenticated user is blocked from ${module.name}`, async ({ browser }) => {
      const context = await browser.newContext({ storageState: undefined });
      const page = await context.newPage();
      await page.goto(`${environment.baseUrl}${module.path}`);
      const currentUrl = page.url();
      const isOnLoginPage = /\/admin\/login/.test(currentUrl);
      const isOnErrorPage = /\/403|\/404|\/500/.test(currentUrl);
      const blocked = isOnLoginPage || isOnErrorPage;
      expect(blocked, `Expected ${module.name} to be protected but reached ${currentUrl}`).toBeTruthy();
      if (isOnLoginPage) {
        await expect(page.getByRole('button', { name: /sign in/i })).toBeVisible();
      }
      await context.close();
    });
  }
});