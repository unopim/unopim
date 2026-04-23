const { test, expect } = require('../../utils/fixtures');
const { navigateTo } = require('../../utils/helpers');

test.describe('AI Agent Export Products - XLSX Support (Issue #684)', () => {

  test('Export Products capability card is visible and clickable', async ({ adminPageWithWidget }) => {
    await navigateTo(adminPageWithWidget, 'dashboard');

    // Open the Agenting PIM panel
    await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
    await expect(adminPageWithWidget.getByText('Agenting PIM')).toBeVisible({ timeout: 5000 });

    // Switch to Capabilities tab
    const capabilitiesTab = adminPageWithWidget.getByText('Capabilities').first();
    await capabilitiesTab.click();

    // Find the Export Products capability card
    const exportCard = adminPageWithWidget.getByRole('button', { name: /Export Products/i });
    await expect(exportCard).toBeVisible({ timeout: 5000 });

    // Verify description mentions CSV/XLSX
    await expect(adminPageWithWidget.getByText(/CSV\/XLSX/i).first()).toBeVisible();
  });

  test('Export Products capability sends prompt to chat when clicked', async ({ adminPageWithWidget }) => {
    await navigateTo(adminPageWithWidget, 'dashboard');

    // Open the Agenting PIM panel
    await adminPageWithWidget.getByRole('button', { name: 'Open Agenting PIM' }).click();
    await expect(adminPageWithWidget.getByText('Agenting PIM')).toBeVisible({ timeout: 5000 });

    // Switch to Capabilities tab
    const capabilitiesTab = adminPageWithWidget.getByText('Capabilities').first();
    await capabilitiesTab.click();

    // Click the Export Products capability
    const exportCard = adminPageWithWidget.getByRole('button', { name: /Export Products/i });
    await exportCard.click();

    // Should switch to Chat tab and show the auto-prompt message
    await expect(adminPageWithWidget.getByText('Chat').first()).toBeVisible({ timeout: 5000 });

    // The chat area should show "Ready for Export Products" prompt
    await expect(adminPageWithWidget.getByText('Ready for Export Products')).toBeVisible({ timeout: 10000 });
  });
});
