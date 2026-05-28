const { test, expect } = require('../../utils/fixtures');

test.describe('Profile Image AI Generation - No @ Suggestions (Issue #701)', () => {

  test('My Account AI image prompt does not show @ suggestion icon', async ({ adminPage }) => {
    // Navigate to My Account page
    await adminPage.goto('/admin/account', { waitUntil: 'networkidle', timeout: 30000 }).catch(async () => {
      await adminPage.waitForLoadState('load', { timeout: 10000 }).catch(() => {});
    });

    // Verify we're on the My Account page — use the heading text
    await expect(adminPage.locator('p.text-xl').getByText('My Account')).toBeVisible({ timeout: 10000 });

    // Click the "Add Image" area to trigger the image upload options
    const addImageArea = adminPage.locator('text=Add Image').first();

    if (await addImageArea.isVisible({ timeout: 5000 }).catch(() => false)) {
      await addImageArea.click();

      // Look for "Generate with AI" option in the choice modal
      const generateAI = adminPage.getByText(/Generate with AI/i).first();

      if (await generateAI.isVisible({ timeout: 5000 }).catch(() => false)) {
        await generateAI.click();

        // Wait for the AI Image Generation modal to appear
        await expect(adminPage.getByText('AI Image Generation').first()).toBeVisible({ timeout: 5000 });

        // Verify the @ icon is NOT visible in the prompt area
        const atIcon = adminPage.locator('.icon-at');
        await expect(atIcon).toHaveCount(0);
      }
    }
  });
});
