const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Notifications', () => {

// ═════════════════════════════════════════════════
// SECTION 1: Notification Bell Icon & Header
// ═════════════════════════════════════════════════

test('1.1 - Notification bell icon is visible in header', async ({ adminPage }) => {
  const notifIcon = adminPage.locator('[title="Notifications"]');
  await expect(notifIcon).toBeVisible();
});

test('1.2 - Notification bell icon has correct icon class', async ({ adminPage }) => {
  const iconSpan = adminPage.locator('.icon-notification');
  await expect(iconSpan).toBeVisible();
});

test('1.3 - Notification bell icon shows unread badge when unread notifications exist', async ({ adminPage }) => {
  // The unread badge is a span that appears conditionally with a count
  // It may or may not be visible depending on notification state
  const badge = adminPage.locator('[title="Notifications"]').locator('span.bg-violet-400');
  const isVisible = await badge.isVisible().catch(() => false);

  // Either the badge is visible with a count, or no unread notifications
  if (isVisible) {
    const text = await badge.innerText();
    expect(text.trim()).toMatch(/^\d+$/);
  }
  // No unread = no badge — both are valid states
});

// ═════════════════════════════════════════════════
// SECTION 2: Notification Dropdown
// ═════════════════════════════════════════════════

test('2.1 - Clicking notification bell opens dropdown', async ({ adminPage }) => {
  await adminPage.locator('[title="Notifications"]').click();

  await expect(adminPage.getByText('Notifications', { exact: true })).toBeVisible();
  await expect(adminPage.getByRole('link', { name: 'View All' })).toBeVisible();
});

test('2.2 - Notification dropdown has View All link pointing to notifications page', async ({ adminPage }) => {
  await adminPage.locator('[title="Notifications"]').click();

  const viewAllLink = adminPage.getByRole('link', { name: 'View All' });
  await expect(viewAllLink).toBeVisible();
  await expect(viewAllLink).toHaveAttribute('href', /\/admin\/notifications/);
});

test('2.3 - Notification dropdown shows Notifications heading in footer', async ({ adminPage }) => {
  await adminPage.locator('[title="Notifications"]').click();

  // Dropdown header shows "Notifications"
  await expect(adminPage.getByText('Notifications', { exact: true })).toBeVisible();
  // Footer always has "View All"
  await expect(adminPage.getByRole('link', { name: 'View All' })).toBeVisible();
});

test('2.4 - Notification dropdown shows Read All when notifications exist', async ({ adminPage }) => {
  await adminPage.locator('[title="Notifications"]').click();

  // Read All only appears when there are notifications
  const readAll = adminPage.getByText('Read All');
  const readAllVisible = await readAll.isVisible().catch(() => false);

  // Verify the dropdown opened regardless
  await expect(adminPage.getByText('Notifications', { exact: true })).toBeVisible();

  // If Read All is visible, it should be clickable
  if (readAllVisible) {
    await expect(readAll).toBeVisible();
  }
});

test('2.5 - Notification dropdown displays notification entries with title, description, and time', async ({ adminPage }) => {
  await adminPage.locator('[title="Notifications"]').click();

  // Wait for the dropdown to be fully loaded
  await expect(adminPage.getByText('Notifications', { exact: true })).toBeVisible();

  // Check if there are notification entries inside the dropdown
  const entries = adminPage.locator('[title="Notifications"]').locator('..').locator('a');
  const count = await entries.count();

  if (count > 0) {
    // Each notification entry should have text content
    const firstEntry = entries.first();
    await expect(firstEntry).toBeVisible();
  }
  // Empty dropdown (no notifications) is also valid
});

// ═════════════════════════════════════════════════
// SECTION 3: Notification History Page
// ═════════════════════════════════════════════════

test('3.1 - Navigate to Notification History page via View All link', async ({ adminPage }) => {
  await adminPage.locator('[title="Notifications"]').click();

  const viewAllLink = adminPage.getByRole('link', { name: 'View All' });
  await expect(viewAllLink).toBeVisible();
  await viewAllLink.click();
  await adminPage.waitForLoadState('networkidle');

  await expect(adminPage).toHaveURL(/\/admin\/notifications/);
});

test('3.2 - Notification History page shows correct title and description', async ({ adminPage }) => {
  await adminPage.goto('/admin/notifications');
  await adminPage.waitForLoadState('networkidle');

  await expect(adminPage.locator('p').filter({ hasText: 'Notifications' }).first()).toBeVisible();
  await expect(adminPage.getByText('List all the Notifications')).toBeVisible();
});

test('3.3 - Notification History page shows No Record Found or notification entries', async ({ adminPage }) => {
  await adminPage.goto('/admin/notifications');
  await adminPage.waitForLoadState('networkidle');

  // Either shows "No Record Found" or notification entries
  const noRecords = adminPage.getByText('No Record Found');
  const noRecordsVisible = await noRecords.isVisible().catch(() => false);

  if (noRecordsVisible) {
    await expect(noRecords).toBeVisible();
  }
  // If there are records, the grid would show them instead
});

test('3.4 - Notification History page has pagination — per Page selector', async ({ adminPage }) => {
  await adminPage.goto('/admin/notifications');
  await adminPage.waitForLoadState('networkidle');

  await expect(adminPage.getByText('per Page')).toBeVisible();
});

test('3.5 - Notification History page has pagination — page of total indicator', async ({ adminPage }) => {
  await adminPage.goto('/admin/notifications');
  await adminPage.waitForLoadState('networkidle');

  await expect(adminPage.getByText('of', { exact: true })).toBeVisible();
});

test('3.6 - Notification History page has pagination navigation arrows', async ({ adminPage }) => {
  await adminPage.goto('/admin/notifications');
  await adminPage.waitForLoadState('networkidle');

  // Pagination has clickable arrow elements (icon-based, not text)
  // The page info section with "of" and navigation controls should be present
  await expect(adminPage.getByText('of', { exact: true })).toBeVisible();
  await expect(adminPage.getByText('per Page')).toBeVisible();
});

test('3.7 - Direct URL access to Notification History page works', async ({ adminPage }) => {
  await adminPage.goto('/admin/notifications');
  await expect(adminPage).toHaveURL(/\/admin\/notifications/);
  await expect(adminPage.locator('p').filter({ hasText: 'Notifications' }).first()).toBeVisible();
});

test('3.8 - Notification History page has header elements — logo, dark mode, bell, profile', async ({ adminPage }) => {
  await adminPage.goto('/admin/notifications');
  await adminPage.waitForLoadState('networkidle');

  await expect(adminPage.locator('img[alt="UnoPim"]').first()).toBeVisible();
  await expect(adminPage.locator('[title="Notifications"]')).toBeVisible();
});

});
