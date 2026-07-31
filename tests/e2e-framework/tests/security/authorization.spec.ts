import { test, expect } from '../../fixtures/base-test';
import { modules } from '../../constants/modules';
import { environment } from '../../config/environment';

test.describe('Authorization', () => {
  for (const module of modules.filter((item) => item.path?.startsWith('/admin') && !item.path.includes('{'))) {
    test(`@authorization unauthenticated user is blocked from ${module.name}`, async ({ browser }) => {
      const context = await browser.newContext({ storageState: undefined });
      const page = await context.newPage();
      await page.goto(`${environment.baseUrl}${module.path!}`);
      await expect(page).toHaveURL(/\/admin\/login/);
      await expect(page.getByRole('button', { name: /sign in/i }).first()).toBeVisible();
      await context.close();
    });
  }
});
