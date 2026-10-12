const { test, expect } = require('../../utils/fixtures');
const path = require('path');

const STORAGE_STATE = path.resolve(__dirname, '../../.state/admin-auth.json');

/**
 * Navigate to the notifications page and wait for Vue component to render.
 * If the stored session has been invalidated by prior tests (e.g. loginpage
 * logout tests), re-authenticate first and persist the fresh session back
 * to admin-auth.json so subsequent tests reuse it.
 */
async function navigateToNotifications(page) {
  await page.goto('/admin/notifications', { waitUntil: 'networkidle' });

  // If redirected to login, re-auth and save fresh state for other tests.
  // Use the field names and submit button class directly — the "Sign In" role
  // name also substring-matches the "Sign in with Microsoft" SSO button, which
  // makes getByRole('button', { name: 'Sign In' }) ambiguous.
  if (page.url().includes('/admin/login')) {
    await page.locator('input[name="email"]').fill('admin@example.com');
    await page.locator('input[name="password"]').fill('admin123');
    await page.locator('.primary-button').click();
    await page.waitForURL(/\/admin\/(?!login)/, { timeout: 15000 });
    await page.context().storageState({ path: STORAGE_STATE });
    await page.goto('/admin/notifications', { waitUntil: 'networkidle' });
  }

  // Wait for Vue component to mount — either notifications list or empty state will appear
  await page.waitForSelector('.icon-notification, a[href*="viewed-notifications"]', { timeout: 15000 });
}

// ─── Notification Page Tests ────────────────────────────────────────

test.describe('Notification Page', () => {
  test('1 - should load the notifications page', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    // Target the page title specifically (not the dropdown header). The page title
    // now renders through x-admin::page-header (<h1>), so match the heading role.
    await expect(adminPage.getByRole('heading', { name: 'Notifications' })).toBeVisible();
    await expect(adminPage.getByText('List all the Notifications')).toBeVisible();
  });

  test('2 - should display status filter tabs (All, Unread, Read)', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    const tabContainer = adminPage.locator('.flex.gap-4.pt-2.border-b');
    await expect(tabContainer.getByText('All', { exact: true })).toBeVisible();
    await expect(tabContainer.getByText('Unread', { exact: true })).toBeVisible();
    await expect(tabContainer.getByText('Read', { exact: true })).toBeVisible();
  });

  test('3 - should switch between tabs', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    const tabContainer = adminPage.locator('.flex.gap-4.pt-2.border-b');

    // Click Unread tab
    await tabContainer.getByText('Unread', { exact: true }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(tabContainer.locator('div.text-primary-700').getByText('Unread')).toBeVisible();

    // Click Read tab
    await tabContainer.getByText('Read', { exact: true }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(tabContainer.locator('div.text-primary-700').getByText('Read', { exact: true })).toBeVisible();

    // Click All tab
    await tabContainer.getByText('All', { exact: true }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(tabContainer.locator('div.text-primary-700').getByText('All')).toBeVisible();
  });

  test('4 - should show empty state or notification list', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);

    // Scope to main content grid (not the header dropdown which also has notification links)
    const notificationItem = adminPage.locator('.grid > a[href*="viewed-notifications"]').first();
    const emptyState = adminPage.getByText('No Record Found');

    // Wait for either state to appear
    await Promise.race([
      notificationItem.waitFor({ state: 'visible', timeout: 10000 }).catch(() => {}),
      emptyState.waitFor({ state: 'visible', timeout: 10000 }).catch(() => {}),
    ]);

    const hasItems = await notificationItem.isVisible().catch(() => false);
    const hasEmpty = await emptyState.isVisible().catch(() => false);

    expect(hasItems || hasEmpty).toBeTruthy();
  });

  test('5 - should display notification bell in header', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    await expect(adminPage.locator('.icon-notification').first()).toBeVisible();
  });

  test('6 - should show Mark as Read button when unread notifications exist', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);

    // Check if there are unread notifications via the badge
    const unreadBadge = adminPage.locator('.bg-primary-100');
    const hasUnread = await unreadBadge.isVisible({ timeout: 3000 }).catch(() => false);

    if (hasUnread) {
      await expect(adminPage.locator('button.transparent-button', { hasText: 'Mark as Read' })).toBeVisible();
    }
  });

  test('7 - should show pagination when notifications exist', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);

    const hasItems = await adminPage.locator('.grid > a[href*="viewed-notifications"]').first().isVisible({ timeout: 3000 }).catch(() => false);

    if (hasItems) {
      // New pagination has chevron buttons and page indicator
      await expect(adminPage.locator('button .icon-chevron-left').last()).toBeVisible();
      await expect(adminPage.locator('button .icon-chevron-right').last()).toBeVisible();
    }
  });

  test('8 - should use full page width for notification list', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);

    // The main container uses flex-col with rounded-md box-shadow
    const container = adminPage.locator('.rounded-md.box-shadow').first();
    const isVisible = await container.isVisible({ timeout: 3000 }).catch(() => false);

    if (isVisible) {
      const box = await container.boundingBox();
      // Container should be wider than 600px (previously it was ~400px with max-w-max)
      expect(box.width).toBeGreaterThan(600);
    }
  });
});
