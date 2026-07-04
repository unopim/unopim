import { expect, APIResponse } from '@playwright/test';

export async function expectSuccessfulJson(response: APIResponse): Promise<void> {
  expect(response.ok(), await response.text()).toBeTruthy();
  expect(response.headers()['content-type'] ?? '').toContain('application/json');
}
