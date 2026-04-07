const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

/**
 * Helper: Open the profile dropdown reliably.
 * Navigates to dashboard first, then waits for network idle.
 */
async function openProfileDropdown(adminPage) {
  await navigateTo(adminPage, 'dashboard');
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await profileBtn.click();
  // Wait for dropdown content to render
  await expect(adminPage.getByRole('link', { name: 'Logout' })).toBeVisible({ timeout: 5000 });
}

test.describe('UnoPim Version Check', () => {

test.beforeEach(async ({ adminPage }) => {
  await navigateTo(adminPage, 'dashboard');
});

// ═════════════════════════════════════════════════
// SECTION 1: Profile Dropdown & Version Display
// ═════════════════════════════════════════════════

test('1.1 - Admin profile button is visible in header', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await expect(profileBtn).toBeVisible();
});

test('1.2 - Clicking profile button opens dropdown', async ({ adminPage }) => {
  await openProfileDropdown(adminPage);

  // Dropdown should show version, My Account, and Logout
  await expect(adminPage.locator('#app').getByText(/Version/)).toBeVisible();
  await expect(adminPage.getByRole('link', { name: 'My Account' })).toBeVisible();
  await expect(adminPage.getByRole('link', { name: 'Logout' })).toBeVisible();
});

test('1.3 - Profile dropdown shows version string in format "Version : vX.X.X"', async ({ adminPage }) => {
  await openProfileDropdown(adminPage);

  const versionLocator = adminPage.locator('#app').getByText(/Version\s*:\s*v\d+\.\d+\.\d+/);
  await expect(versionLocator).toBeVisible();
  const versionText = await versionLocator.innerText();
  expect(versionText).toMatch(/Version\s*:\s*v\d+\.\d+\.\d+/);
});

test('1.4 - Version displays v2.0.1', async ({ adminPage }) => {
  await openProfileDropdown(adminPage);

  await expect(adminPage.locator('#app').getByText(/Version\s*:\s*v2\.0\.1/)).toBeVisible();
});

test('1.5 - Profile dropdown shows UnoPim logo icon next to version', async ({ adminPage }) => {
  await openProfileDropdown(adminPage);

  const logo = adminPage.locator('img[src*="unopim"]');
  await expect(logo.first()).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 2: Profile Dropdown Links
// ═════════════════════════════════════════════════

test('2.1 - Profile dropdown shows My Account link with correct URL', async ({ adminPage }) => {
  await openProfileDropdown(adminPage);

  const myAccountLink = adminPage.getByRole('link', { name: 'My Account' });
  await expect(myAccountLink).toBeVisible();
  await expect(myAccountLink).toHaveAttribute('href', /\/admin\/account/);
});

test('2.2 - Profile dropdown shows Logout link', async ({ adminPage }) => {
  await openProfileDropdown(adminPage);

  const logoutLink = adminPage.getByRole('link', { name: 'Logout' });
  await expect(logoutLink).toBeVisible();
});

test('2.3 - My Account link navigates to account edit page', async ({ adminPage }) => {
  await openProfileDropdown(adminPage);

  const myAccountLink = adminPage.getByRole('link', { name: 'My Account' });
  await expect(myAccountLink).toBeVisible();
  await myAccountLink.click();
  await expect(adminPage).toHaveURL(/\/admin\/account/);
});

// ═════════════════════════════════════════════════
// SECTION 3: Header Dark Mode Toggle
// ═════════════════════════════════════════════════

test('3.1 - Dark mode toggle icon is visible in header', async ({ adminPage }) => {
  const darkToggle = adminPage.locator('.icon-dark, .icon-light');
  await expect(darkToggle.first()).toBeVisible();
});

test('3.2 - Clicking dark mode toggle switches the icon', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const darkIcon = adminPage.locator('.icon-dark');
  const lightIcon = adminPage.locator('.icon-light');

  const wasDark = await darkIcon.isVisible().catch(() => false);

  // Click the toggle
  const toggle = adminPage.locator('.icon-dark, .icon-light').first();
  await toggle.click();

  if (wasDark) {
    // Should now show light icon
    await expect(lightIcon).toBeVisible();
  } else {
    // Should now show dark icon
    await expect(darkIcon).toBeVisible();
  }

  // Toggle back to restore original state
  const toggleBack = adminPage.locator('.icon-dark, .icon-light').first();
  await toggleBack.click();
});

});
