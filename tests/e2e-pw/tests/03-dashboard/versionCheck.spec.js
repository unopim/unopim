const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Version Check', () => {

// ═════════════════════════════════════════════════
// SECTION 1: Profile Dropdown & Version Display
// ═════════════════════════════════════════════════

test('1.1 - Admin profile button is visible in header', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await expect(profileBtn).toBeVisible();
});

test('1.2 - Clicking profile button opens dropdown', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await profileBtn.click();

  // Dropdown should show version, My Account, and Logout
  await expect(adminPage.locator('#app').getByText(/Version/)).toBeVisible();
  await expect(adminPage.getByRole('link', { name: 'My Account' })).toBeVisible();
  await expect(adminPage.getByRole('link', { name: 'Logout' })).toBeVisible();
});

test('1.3 - Profile dropdown shows version string in format "Version : vX.X.X"', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await profileBtn.click();

  const versionLocator = adminPage.locator('#app').getByText(/Version\s*:\s*v\d+\.\d+\.\d+/);
  await expect(versionLocator).toBeVisible();
  const versionText = await versionLocator.innerText();
  expect(versionText).toMatch(/Version\s*:\s*v\d+\.\d+\.\d+/);
});

test('1.4 - Version displays v2.0.0', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await profileBtn.click();

  await expect(adminPage.locator('#app').getByText(/Version\s*:\s*v2\.0\.0/)).toBeVisible();
});

test('1.5 - Profile dropdown shows UnoPim logo icon next to version', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await profileBtn.click();

  const logo = adminPage.locator('img[src*="unopim"]');
  await expect(logo.first()).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 2: Profile Dropdown Links
// ═════════════════════════════════════════════════

test('2.1 - Profile dropdown shows My Account link with correct URL', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await profileBtn.click();

  const myAccountLink = adminPage.getByRole('link', { name: 'My Account' });
  await expect(myAccountLink).toBeVisible();
  await expect(myAccountLink).toHaveAttribute('href', /\/admin\/account/);
});

test('2.2 - Profile dropdown shows Logout link', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await profileBtn.click();

  const logoutLink = adminPage.getByRole('link', { name: 'Logout' });
  await expect(logoutLink).toBeVisible();
});

test('2.3 - My Account link navigates to account edit page', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await profileBtn.click();

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
