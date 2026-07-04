import { test, expect } from '../../fixtures/base-test';
import { modules } from '../../constants/modules';
import { AccessibilityUtility } from '../../utils/accessibility';

const uiModules = modules.filter((module) => module.path && module.path.startsWith('/admin') && !module.path.includes('{'));

test.describe('Discovered admin modules', () => {
  for (const module of uiModules) {
    test(`@smoke ${module.name} page loads`, async ({ page, crudPage }) => {
      await crudPage.goto(module.path!);
      await crudPage.expectIndexReady();
      await expect(page.locator('body')).not.toContainText(/403|404|500|exception|stack trace/i);
    });

    test(`@regression ${module.name} handles hostile search text`, async ({ crudPage }) => {
      await crudPage.goto(module.path!);
      await crudPage.assertSearchDoesNotCrash(`"><script>alert(1)</script>' OR 1=1 --`);
    });

    test(`@keyboard ${module.name} supports focus navigation`, async ({ crudPage }) => {
      await crudPage.goto(module.path!);
      await crudPage.assertKeyboardTabOrder();
    });

    test(`@a11y ${module.name} has no critical automated accessibility violations`, async ({ page, crudPage }) => {
      await crudPage.goto(module.path!);
      await new AccessibilityUtility(page).assertNoCriticalViolations();
    });
  }
});
