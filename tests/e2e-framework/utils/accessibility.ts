import AxeBuilder from '@axe-core/playwright';
import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';

export class AccessibilityUtility {
  constructor(private readonly page: Page) {}

  async assertNoCriticalViolations(): Promise<void> {
    const results = await new AxeBuilder({ page: this.page })
      .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
      .analyze();

    const critical = results.violations.filter((violation) => ['critical', 'serious'].includes(violation.impact ?? ''));
    expect(critical, JSON.stringify(critical, null, 2)).toEqual([]);
  }
}
