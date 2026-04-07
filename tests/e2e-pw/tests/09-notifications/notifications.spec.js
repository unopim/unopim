const { test, expect } = require('../../utils/fixtures');

/**
 * Navigate to the notifications page.
 */
async function navigateToNotifications(page) {
  await page.goto('/admin/notifications', { waitUntil: 'networkidle' });
}

// ─── Notification Page Tests ────────────────────────────────────────

test.describe('Notification Page', () => {
  test('1 - should load the notifications page', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    await expect(adminPage.locator('#app').getByText('Notifications').first()).toBeVisible();
    await expect(adminPage.locator('#app').getByText('List all the Notifications')).toBeVisible();
  });

  test('2 - should display status filter tabs (All, Unread, Read)', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    await expect(adminPage.getByRole('button', { name: 'All' })).toBeVisible();
    await expect(adminPage.getByRole('button', { name: 'Unread' })).toBeVisible();
    await expect(adminPage.getByRole('button', { name: 'Read' })).toBeVisible();
  });

  test('3 - should switch between tabs', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);

    // Click Unread tab
    await adminPage.getByRole('button', { name: 'Unread' }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.getByRole('button', { name: 'Unread' })).toHaveClass(/text-violet-700/);

    // Click Read tab
    await adminPage.getByRole('button', { name: 'Read' }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.getByRole('button', { name: 'Read' })).toHaveClass(/text-violet-700/);

    // Click All tab
    await adminPage.getByRole('button', { name: 'All' }).click();
    await adminPage.waitForLoadState('networkidle');
    await expect(adminPage.getByRole('button', { name: 'All' })).toHaveClass(/text-violet-700/);
  });

  test('4 - should show empty state or notification list', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    await adminPage.waitForLoadState('networkidle');

    // Either notifications are visible or the empty state is shown
    const hasNotifications = await adminPage.locator('.icon-notification.text-5xl').isVisible().catch(() => false);
    const hasItems = await adminPage.locator('a[href*="viewed-notifications"]').first().isVisible().catch(() => false);

    expect(hasNotifications || hasItems).toBeTruthy();
  });

  test('5 - should display notification bell in header', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    await expect(adminPage.locator('.icon-notification').first()).toBeVisible();
  });

  test('6 - should show Mark as Read button when unread notifications exist', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    await adminPage.waitForLoadState('networkidle');

    // Check if there are unread notifications
    const unreadBadge = adminPage.locator('.bg-violet-100');
    const hasUnread = await unreadBadge.isVisible({ timeout: 3000 }).catch(() => false);

    if (hasUnread) {
      await expect(adminPage.getByRole('button', { name: 'Mark as Read' })).toBeVisible();
    }
  });

  test('7 - should show pagination when notifications exist', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    await adminPage.waitForLoadState('networkidle');

    const hasItems = await adminPage.locator('a[href*="viewed-notifications"]').first().isVisible({ timeout: 3000 }).catch(() => false);

    if (hasItems) {
      // Pagination section should be visible
      await expect(adminPage.locator('.icon-chevron-left').last()).toBeVisible();
      await expect(adminPage.locator('.icon-chevron-right').last()).toBeVisible();
    }
  });

  test('8 - should use full page width for notification list', async ({ adminPage }) => {
    await navigateToNotifications(adminPage);
    await adminPage.waitForLoadState('networkidle');

    // The main container should NOT have max-w-max (old bug) — it should span full width
    const container = adminPage.locator('.bg-white.rounded-md.box-shadow, .dark\\:bg-cherry-900.rounded-md.box-shadow').first();
    const isVisible = await container.isVisible({ timeout: 3000 }).catch(() => false);

    if (isVisible) {
      const box = await container.boundingBox();
      // Container should be wider than 600px (previously it was ~400px with max-w-max)
      expect(box.width).toBeGreaterThan(600);
    }
  });
});
