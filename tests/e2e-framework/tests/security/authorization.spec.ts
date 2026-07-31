import { test, expect } from '../../fixtures/base-test';
import { modules } from '../../constants/modules';
import { environment } from '../../config/environment';

test.describe('Authorization', () => {
  for (const module of modules.filter((item) => item.path?.startsWith('/admin') && !item.path.includes('{'))) {
    test(`@authorization unauthenticated user is blocked from ${module.name}`, async ({ browser }) => {
      const context = await browser.newContext({ storageState: undefined });
      const page = await context.newPage();
      await page.goto(`${environment.baseUrl}${module.path!}`);
      const url = page.url();
      const isOnLoginPage = /\/admin\/login/.test(url);
      const isOnErrorPage = /\/403|\/404|\/500/.test(url) || /403 Forbidden|404 Not Found|500 Internal Server Error/i.test(await page.locator('body').textContent() ?? '');
      expect(isOnLoginPage || isOnErrorPage, `Expected to be blocked from ${module.name}, but landed on ${url}`).toBeTruthy();
      await context.close();
    });
  }
});
