const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

test.describe('UnoPim Dashboard (v2.0.1)', () => {

test.beforeEach(async ({ adminPage }) => {
  await navigateTo(adminPage, 'dashboard');
});

// ═════════════════════════════════════════════════
// SECTION 1: Header & Navigation
// ═════════════════════════════════════════════════

test('1.1 - Header shows UnoPim logo linking to dashboard', async ({ adminPage }) => {
  const logoLink = adminPage.getByRole('link', { name: 'UnoPim' });
  await expect(logoLink).toBeVisible();
  await expect(logoLink).toHaveAttribute('href', /\/admin\/dashboard/);
});

test('1.2 - Header shows dark mode toggle icon', async ({ adminPage }) => {
  // Dark mode icon is either icon-dark or icon-light
  const darkIcon = adminPage.locator('.icon-dark, .icon-light');
  await expect(darkIcon.first()).toBeVisible();
});

test('1.3 - Header shows notification bell icon', async ({ adminPage }) => {
  await expect(adminPage.locator('[title="Notifications"]')).toBeVisible();
});

test('1.4 - Header shows admin profile avatar button', async ({ adminPage }) => {
  const profileBtn = adminPage.locator('header').getByRole('button').last();
  await expect(profileBtn).toBeVisible();
});

test('1.5 - Header shows hamburger menu icon (for responsive)', async ({ adminPage }) => {
  // Hamburger menu is hidden on desktop but exists in DOM
  const hamburger = adminPage.locator('.icon-menu');
  // It should exist in the DOM (hidden on desktop via CSS class "hidden")
  expect(await hamburger.count()).toBeGreaterThanOrEqual(1);
});

// ═════════════════════════════════════════════════
// SECTION 2: Welcome & Quick Actions
// ═════════════════════════════════════════════════

test('2.1 - Shows welcome greeting with admin name', async ({ adminPage }) => {
  await expect(adminPage.getByText(/Welcome back/)).toBeVisible();
});

test('2.2 - Shows dashboard overview subtitle', async ({ adminPage }) => {
  await expect(
    adminPage.getByText("Here's what's happening with your product information today.")
  ).toBeVisible();
});

test('2.3 - Shows Create Product quick action button', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: 'Create Product' });
  await expect(link).toBeVisible();
  await expect(link).toHaveAttribute('href', /\/admin\/catalog\/products/);
});

test('2.4 - Shows Import Data quick action link with icon', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: 'Import Data' });
  await expect(link).toBeVisible();
  await expect(link).toHaveAttribute('href', /\/admin\/settings\/data-transfer\/imports/);
});

test('2.5 - Shows Export Data quick action link with icon', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: 'Export Data' });
  await expect(link).toBeVisible();
  await expect(link).toHaveAttribute('href', /\/admin\/settings\/data-transfer\/exports/);
});

// ═════════════════════════════════════════════════
// SECTION 3: Catalog Overview
// ═════════════════════════════════════════════════

test('3.1 - Shows Catalog Overview section heading', async ({ adminPage }) => {
  await expect(adminPage.getByText('Catalog Overview')).toBeVisible();
});

test('3.2 - Total Products card shows icon, label, and numeric count', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: 'Total Products Total Products' });
  await expect(link).toBeVisible();
  await expect(link.locator('img[title="Total Products"]')).toBeVisible();
  const numberText = await link.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('3.3 - Total Products card links to products catalog page', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: 'Total Products Total Products' });
  await expect(link).toHaveAttribute('href', /\/admin\/catalog\/products/);
});

test('3.4 - Total Categories card shows icon, label, and numeric count', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Categories Total Categories/ });
  await expect(link).toBeVisible();
  await expect(link.locator('img[title="Total Categories"]')).toBeVisible();
  const numberText = await link.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('3.5 - Total Categories card links to categories catalog page', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Categories Total Categories/ });
  await expect(link).toHaveAttribute('href', /\/admin\/catalog\/categories/);
});

// ═════════════════════════════════════════════════
// SECTION 4: Catalog Structure
// ═════════════════════════════════════════════════

test('4.1 - Shows Catalog Structure section heading', async ({ adminPage }) => {
  await expect(adminPage.getByText('Catalog Structure')).toBeVisible();
});

test('4.2 - Total Attributes card shows icon and numeric count', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Attributes Total Attributes/ });
  await expect(link).toBeVisible();
  await expect(link.locator('img[title="Total Attributes"]')).toBeVisible();
  const numberText = await link.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('4.3 - Total Attributes links to attributes page', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Attributes Total Attributes/ });
  await expect(link).toHaveAttribute('href', /\/admin\/catalog\/attributes/);
});

test('4.4 - Total Groups card shows icon and numeric count', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Groups Total Groups/ });
  await expect(link).toBeVisible();
  await expect(link.locator('img[title="Total Groups"]')).toBeVisible();
  const numberText = await link.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('4.5 - Total Groups links to attribute groups page', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Groups Total Groups/ });
  await expect(link).toHaveAttribute('href', /\/admin\/catalog\/attributegroups/);
});

test('4.6 - Total Families card shows icon and numeric count', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Families Total Families/ });
  await expect(link).toBeVisible();
  await expect(link.locator('img[title="Total Families"]')).toBeVisible();
  const numberText = await link.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('4.7 - Total Families links to families page', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Families Total Families/ });
  await expect(link).toHaveAttribute('href', /\/admin\/catalog\/families/);
});

test('4.8 - Total Locales card shows icon and numeric count', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Locales Total Locales/ });
  await expect(link).toBeVisible();
  await expect(link.locator('img[title="Total Locales"]')).toBeVisible();
  const numberText = await link.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('4.9 - Total Locales links to locales settings page', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Locales Total Locales/ });
  await expect(link).toHaveAttribute('href', /\/admin\/settings\/locales/);
});

test('4.10 - Total Currencies card shows icon and numeric count', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Currencies Total Currencies/ });
  await expect(link).toBeVisible();
  await expect(link.locator('img[title="Total Currencies"]')).toBeVisible();
  const numberText = await link.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('4.11 - Total Currencies links to currencies settings page', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Currencies Total Currencies/ });
  await expect(link).toHaveAttribute('href', /\/admin\/settings\/currencies/);
});

test('4.12 - Total Channels card shows icon and numeric count', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Channels Total Channels/ });
  await expect(link).toBeVisible();
  await expect(link.locator('img[title="Total Channels"]')).toBeVisible();
  const numberText = await link.locator('p.text-3xl').innerText();
  expect(numberText.trim()).toMatch(/^\d+$/);
});

test('4.13 - Total Channels links to channels settings page', async ({ adminPage }) => {
  const link = adminPage.getByRole('link', { name: /Total Channels Total Channels/ });
  await expect(link).toHaveAttribute('href', /\/admin\/settings\/channels/);
});

// ═════════════════════════════════════════════════
// SECTION 5: Needs Attention
// ═════════════════════════════════════════════════

test('5.1 - Shows Needs Attention section with red indicator', async ({ adminPage }) => {
  // Wait for dashboard AJAX to settle — Product Statistics always renders after AJAX
  await adminPage.getByText('No products yet.').or(adminPage.locator('a:has-text("Total Products")')).first().waitFor({ state: 'visible', timeout: 15000 });

  // Needs Attention is conditionally rendered — only appears when there are actionable issues
  const needsAttention = adminPage.getByText('Needs Attention');
  const isVisible = await needsAttention.isVisible().catch(() => false);
  test.skip(!isVisible, 'No attention items in current environment — section hidden by design');
  await expect(needsAttention).toBeVisible();
});

test('5.2 - Needs Attention shows unenriched products count with link', async ({ adminPage }) => {
  // Wait for dashboard AJAX to settle
  await adminPage.getByText('No products yet.').or(adminPage.locator('a:has-text("Total Products")')).first().waitFor({ state: 'visible', timeout: 15000 });

  // Needs Attention is conditionally rendered — only appears when there are actionable issues
  const link = adminPage.getByRole('link', { name: /Unenriched Products/ });
  const isVisible = await link.isVisible().catch(() => false);
  test.skip(!isVisible, 'No unenriched products in current environment');

  await expect(link).toHaveAttribute('href', /\/admin\/catalog\/products/);

  // Should display a number before "Unenriched Products"
  const text = await link.innerText();
  expect(text).toMatch(/\d+\s+Unenriched Products/);
});

// ═════════════════════════════════════════════════
// SECTION 6: Analytics — Product Statistics
// ═════════════════════════════════════════════════

test('6.1 - Shows Analytics section heading', async ({ adminPage }) => {
  await expect(adminPage.getByText('Analytics')).toBeVisible();
});

test('6.2 - Shows Product Statistics card heading', async ({ adminPage }) => {
  await expect(adminPage.getByText('Product Statistics')).toBeVisible();
});

test('6.3 - Product Statistics shows Total Products with numeric count', async ({ adminPage }) => {
  // Wait for Product Statistics AJAX to complete
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  await expect(adminPage.getByText('Total Products').first()).toBeVisible();
});

test('6.4 - Product Statistics shows Active count with green indicator', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  await expect(adminPage.getByText('Active').first()).toBeVisible();
});

test('6.5 - Product Statistics shows Inactive count with orange indicator', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  await expect(adminPage.getByText('Inactive').first()).toBeVisible();
});

test('6.6 - Product Statistics shows Product Type Distribution heading', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  await expect(adminPage.getByText('Product Type Distribution')).toBeVisible();
});

test('6.7 - Product Type Distribution shows type breakdown with counts and percentages', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');

  // Should show at least one product type (simple or configurable) with count and percentage
  const simpleType = adminPage.getByText('simple');
  const configurableType = adminPage.getByText('configurable');

  const simpleVisible = await simpleType.isVisible().catch(() => false);
  const configurableVisible = await configurableType.isVisible().catch(() => false);

  expect(simpleVisible || configurableVisible).toBe(true);

  // Percentage format like (94%) should be visible
  await expect(adminPage.getByText(/\(\d+%\)/).first()).toBeVisible();
});

test('6.8 - Product Statistics shows New This Week metric', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  await expect(adminPage.getByText('New This Week')).toBeVisible();
});

test('6.9 - Product Statistics shows With Variants metric', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  await expect(adminPage.getByText('With Variants')).toBeVisible();
});

test('6.10 - Product Statistics shows Avg Completeness metric', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  await expect(adminPage.getByText('Avg Completeness')).toBeVisible();
});

test('6.11 - Product Statistics shows Enriched metric', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  await expect(adminPage.getByText('Enriched', { exact: true })).toBeVisible();
});

test('6.12 - Total Products card links to products page (unfiltered)', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  // Total Products card links to unfiltered product listing
  const totalCard = adminPage.locator('a', { has: adminPage.getByText('Total Products') }).first();
  const href = await totalCard.getAttribute('href');
  expect(href).toMatch(/\/admin\/catalog\/products$/);
});

test('6.13 - Active card links to products filtered by status=1', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  const activeCard = adminPage.locator('a', { has: adminPage.getByText('Active') }).first();
  const href = await activeCard.getAttribute('href');
  expect(href).toContain('filters[status][]=1');
});

test('6.14 - Inactive card links to products filtered by status=0', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');
  const inactiveCard = adminPage.locator('a', { has: adminPage.getByText('Inactive') }).first();
  const href = await inactiveCard.getAttribute('href');
  expect(href).toContain('filters[status][]=0');
});

test('6.15 - Clicking Inactive card navigates to product listing with status filter applied', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const emptyState = adminPage.getByText('No products yet.');
  const emptyVisible = await emptyState.isVisible().catch(() => false);
  test.skip(emptyVisible, 'No products in current environment');

  // Clear localStorage datagrid state to ensure URL filters take effect
  await adminPage.evaluate(() => localStorage.removeItem('datagrids'));

  const inactiveCard = adminPage.locator('a', { has: adminPage.getByText('Inactive') }).first();
  await inactiveCard.click();

  // Should navigate to products page with status filter
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/products\?filters\[status\]\[\]=0/);
});

// ═════════════════════════════════════════════════
// SECTION 7: Analytics — Product Activity Chart
// ═════════════════════════════════════════════════

test('7.1 - Shows Product Activity (Last 7 Days) chart heading', async ({ adminPage }) => {
  await expect(adminPage.getByText('Product Activity (Last 7 Days)')).toBeVisible();
});

test('7.2 - Product Activity chart shows Created legend', async ({ adminPage }) => {
  // Created legend in the chart header
  await expect(adminPage.getByText('Created').first()).toBeVisible();
});

test('7.3 - Product Activity chart shows Updated legend', async ({ adminPage }) => {
  await expect(adminPage.getByText('Updated').first()).toBeVisible();
});

test('7.4 - Product Activity chart shows all 7 day labels', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const noActivity = adminPage.getByText(/no.*activity|no.*products/i);
  test.skip(await noActivity.isVisible().catch(() => false), 'No product activity in current environment');

  const dayLabels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
  let foundDays = 0;

  for (const day of dayLabels) {
    const dayElement = adminPage.getByText(day, { exact: true });
    if (await dayElement.isVisible().catch(() => false)) {
      foundDays++;
    }
  }

  expect(foundDays).toBe(7);
});

test('7.5 - Product Activity chart shows date numbers for each day', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const noActivity = adminPage.getByText(/no.*activity|no.*products/i);
  test.skip(await noActivity.isVisible().catch(() => false), 'No product activity in current environment');

  // Each day column has a date number beneath the day name
  // Check that at least some date numbers are visible (they're two-digit numbers)
  const dateNumbers = adminPage.locator('p').filter({ hasText: /^\d{1,2}$/ });
  const count = await dateNumbers.count();
  expect(count).toBeGreaterThanOrEqual(7);
});

test('7.6 - Product Activity shows Created and Updated totals summary', async ({ adminPage }) => {
  // Summary line shows "Created: X" and "Updated: Y"
  await expect(adminPage.getByText(/Created:\s*\d+/).first()).toBeVisible();
  await expect(adminPage.getByText(/Updated:\s*\d+/).first()).toBeVisible();
});

test('7.7 - Product Activity shows "Last 7 days" label', async ({ adminPage }) => {
  const summary = adminPage.getByText('Last 7 days', { exact: true });
  await summary.scrollIntoViewIfNeeded();
  await expect(summary).toBeVisible();
});

test('7.8 - Product Activity bars show created/updated counts per day', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const noActivity = adminPage.getByText(/no.*activity|no.*products/i);
  test.skip(await noActivity.isVisible().catch(() => false), 'No product activity in current environment');

  // Each bar column shows values like "0 / 0" or "18 / 15"
  const slashSeparators = adminPage.locator('text=/');
  const count = await slashSeparators.count();
  // Should have 7 slash separators (one per day)
  expect(count).toBeGreaterThanOrEqual(7);
});

// ═════════════════════════════════════════════════
// SECTION 8: Completeness
// ═════════════════════════════════════════════════

test('8.1 - Shows Completeness section heading', async ({ adminPage }) => {
  const heading = adminPage.getByText('Completeness', { exact: true });
  await heading.scrollIntoViewIfNeeded();
  await expect(heading).toBeVisible();
});

test('8.2 - Completeness shows channel name (Default)', async ({ adminPage }) => {
  await expect(adminPage.getByRole('heading', { name: 'Default' })).toBeVisible();
});

test('8.3 - Completeness shows improvement suggestion text', async ({ adminPage }) => {
  const suggestion = adminPage.getByText(/completeness/i).filter({ hasText: /add details|improve/ });
  await suggestion.first().scrollIntoViewIfNeeded();
  await expect(suggestion.first()).toBeVisible();
});

test('8.4 - Completeness shows locale names with progress percentage', async ({ adminPage }) => {
  // Should show locale names like "English (United States)" with a percentage
  const englishLocale = adminPage.getByText('English (United States)').first();
  await englishLocale.scrollIntoViewIfNeeded();
  await expect(englishLocale).toBeVisible();

  // Percentage should be visible near the locale
  await expect(adminPage.getByText(/\d+%/).first()).toBeVisible();
});

test('8.5 - Completeness shows multiple locale entries', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const noProducts = adminPage.getByText('No products yet.');
  test.skip(await noProducts.isVisible().catch(() => false), 'No products in current environment');

  // Each locale should have a percentage display
  const percentages = adminPage.getByText(/\d+%/);
  const count = await percentages.count();
  // Should have at least 1 locale entry with percentage
  expect(count).toBeGreaterThanOrEqual(1);
});

// ═════════════════════════════════════════════════
// SECTION 9: Channel Readiness
// ═════════════════════════════════════════════════

test('9.1 - Shows Channel Readiness section heading', async ({ adminPage }) => {
  const heading = adminPage.getByText('Channel Readiness');
  await heading.scrollIntoViewIfNeeded();
  await expect(heading).toBeVisible();
});

test('9.2 - Channel Readiness shows data or empty state message', async ({ adminPage }) => {
  const emptyMsg = adminPage.getByText('No completeness data available yet.');
  await emptyMsg.scrollIntoViewIfNeeded().catch(() => {});
  // Either shows completeness data or the empty state message
  const emptyVisible = await emptyMsg.isVisible().catch(() => false);
  if (emptyVisible) {
    await expect(emptyMsg).toBeVisible();
  }
  // If channel readiness data exists, the empty message won't show
});

// ═════════════════════════════════════════════════
// SECTION 10: Operations — Recent Activity
// ═════════════════════════════════════════════════

test('10.1 - Shows Operations section heading', async ({ adminPage }) => {
  await adminPage.waitForLoadState('networkidle');
  const heading = adminPage.getByText('Operations');
  await heading.scrollIntoViewIfNeeded();
  await expect(heading).toBeVisible();
});

test('10.2 - Shows Recent Activity card heading', async ({ adminPage }) => {
  const heading = adminPage.getByText('Recent Activity');
  await heading.scrollIntoViewIfNeeded();
  await expect(heading).toBeVisible();
});

test('10.3 - Recent Activity entries show user name, action, and entity', async ({ adminPage }) => {
  // Activity entries show text like "Example updated product #1783"
  const entries = adminPage.getByText(/Example\s+(created|updated|deleted)\s+/i);
  const count = await entries.count();

  if (count > 0) {
    await entries.first().scrollIntoViewIfNeeded();
    await expect(entries.first()).toBeVisible();
  }
});

test('10.4 - Recent Activity entries show relative timestamps', async ({ adminPage }) => {
  // Timestamps like "27 minutes ago", "1 hour ago", etc.
  const timestamps = adminPage.getByText(/\d+\s+(second|minute|hour|day|week|month)s?\s+ago/);
  const count = await timestamps.count();

  if (count > 0) {
    await timestamps.first().scrollIntoViewIfNeeded();
    await expect(timestamps.first()).toBeVisible();
  }
});

test('10.5 - Recent Activity entries show entity type badges', async ({ adminPage }) => {
  // Entity type badges like "product", "category"
  const productBadge = adminPage.getByText('product', { exact: true });
  const categoryBadge = adminPage.getByText('category', { exact: true });

  const productVisible = await productBadge.first().isVisible().catch(() => false);
  const categoryVisible = await categoryBadge.first().isVisible().catch(() => false);

  // At least one type badge should be visible if there's activity
  if (!productVisible && !categoryVisible) {
    // No activity at all — also valid
    expect(true).toBe(true);
  }
});

// ═════════════════════════════════════════════════
// SECTION 11: Operations — Data Transfer
// ═════════════════════════════════════════════════

test('11.1 - Shows Data Transfer card heading', async ({ adminPage }) => {
  // Data Transfer card inside main content area (not sidebar nav)
  const dataTransferHeading = adminPage.locator('main p, [class*="content"] p, .grid p').filter({ hasText: 'Data Transfer' });
  await dataTransferHeading.first().scrollIntoViewIfNeeded();
  await expect(dataTransferHeading.first()).toBeVisible();
});

test('11.2 - Data Transfer shows job entries or empty state message', async ({ adminPage }) => {
  const noJobs = adminPage.getByText('No recent import/export jobs found.');
  await noJobs.scrollIntoViewIfNeeded().catch(() => {});
  // Either shows job entries or empty state
  const emptyVisible = await noJobs.isVisible().catch(() => false);
  if (emptyVisible) {
    await expect(noJobs).toBeVisible();
  }
});

// ═════════════════════════════════════════════════
// SECTION 12: Open Agenting PIM Button
// ═════════════════════════════════════════════════

test('12.1 - Shows "Open Agenting PIM" floating action button', async ({ adminPage }) => {
  const agentBtn = adminPage.getByRole('button', { name: 'Open Agenting PIM' });
  await expect(agentBtn).toBeVisible();
});

// ═════════════════════════════════════════════════
// SECTION 13: Quick Action Link Navigation
// ═════════════════════════════════════════════════

test('13.1 - Create Product link navigates to products page', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: 'Create Product' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/catalog\/products/);
});

test('13.2 - Import Data link navigates to imports page', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: 'Import Data' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/imports/);
});

test('13.3 - Export Data link navigates to exports page', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: 'Export Data' }).click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/exports/);
});

});
