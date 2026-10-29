const { test, expect } = require('../../utils/fixtures');
const { clickSave, generateUid } = require('../../utils/helpers');

const MAGIC_AI_PROMPT_URL = '/admin/magic-ai/prompts';

/**
 * Editing a prompt offers the same "@" attribute suggestions as creating one.
 */
test.describe('Magic AI prompt edit — attribute suggestions', () => {
  test('typing @ in the edit form suggests attributes and the pick is saved', async ({ adminPage }) => {
    const page = adminPage;
    const title = `PW Suggest ${generateUid()}`;

    await page.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: 'Create Prompt' }).click();
    await expect(page.locator('#app').getByText('Create New Prompt')).toBeVisible();
    await page.locator('input[name="title"]').fill(title);
    await page.locator('textarea[name="prompt"]').fill('Describe the product');
    await clickSave(page, 'Save Prompt');
    await expect(page.locator('#app').getByText('Prompt saved successfully.')).toBeVisible();

    try {
      await page.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
      await page.getByRole('textbox', { name: 'Search', exact: true }).fill(title);
      await page.keyboard.press('Enter');
      await page.waitForLoadState('networkidle');

      await page.locator('div', { hasText: title }).locator('span[title="Edit"]').first().click();
      await expect(page).toHaveURL(/\/admin\/magic-ai\/edit\/\d+/);

      const prompt = page.locator('textarea[name="prompt"]');
      await prompt.click();
      await prompt.press('Control+End');
      await page.keyboard.type(' @na', { delay: 50 });

      const suggestion = page.locator('.tribute-container li', { hasText: /^Name$/ }).first();
      await expect(suggestion).toBeVisible();
      await suggestion.click();

      await expect(prompt).toHaveValue(/@name/);

      await clickSave(page, 'Save Prompt');
      await page.reload({ waitUntil: 'networkidle' });

      await expect(page.locator('textarea[name="prompt"]')).toHaveValue(/Describe the product @name/);
    } finally {
      await page.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
      await page.getByRole('textbox', { name: 'Search', exact: true }).fill(title);
      await page.keyboard.press('Enter');
      await page.waitForLoadState('networkidle');

      const remove = page.locator('div', { hasText: title }).locator('span[title="delete"]').first();
      if (await remove.isVisible().catch(() => false)) {
        await remove.click();
        await page.getByRole('button', { name: 'Delete' }).click();
        await page.waitForLoadState('networkidle');
      }
    }
  });
});
