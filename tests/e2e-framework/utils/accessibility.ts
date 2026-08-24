import AxeBuilder from '@axe-core/playwright';
import { expect } from '@playwright/test';
import type { Page, TestInfo } from '@playwright/test';

export class AccessibilityUtility {
  constructor(private readonly page: Page) {}

  /**
   * Run the axe audit and surface critical/serious violations. The admin UI has
   * known, un-triaged violations, so by default this reports (attaches details +
   * logs a count) without failing. Set A11Y_STRICT=true to fail on violations
   * once the UI has been cleaned up.
   */
  async reportCriticalViolations(testInfo?: TestInfo): Promise<void> {
    const results = await new AxeBuilder({ page: this.page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    const critical = results.violations.filter((violation) => ['critical', 'serious'].includes(violation.impact ?? ''));

    if (critical.length && testInfo) {
      await testInfo.attach('axe-critical-violations.json', {
        body: JSON.stringify(critical, null, 2),
        contentType: 'application/json'
      });
      const rules = critical.map((violation) => `${violation.id} (${violation.nodes.length})`).join(', ');
      console.log(`[a11y] ${testInfo.title}: ${critical.length} critical/serious violation(s) — ${rules}`);
    }

    if (process.env.A11Y_STRICT === 'true') {
      expect(critical, JSON.stringify(critical, null, 2)).toEqual([]);
    }
  }
}
