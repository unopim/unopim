import type { Page, Route } from '@playwright/test';

export class NetworkUtility {
  constructor(private readonly page: Page) {}

  async failMatching(urlPattern: string | RegExp): Promise<void> {
    await this.page.route(urlPattern, (route: Route) => route.abort('failed'));
  }

  async slowMatching(urlPattern: string | RegExp, delayMs = 1500): Promise<void> {
    await this.page.route(urlPattern, async (route) => {
      await new Promise((resolve) => setTimeout(resolve, delayMs));
      await route.continue();
    });
  }
}
