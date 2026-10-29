const { test, expect } = require('../../utils/fixtures');
const { clickSave, generateUid } = require('../../utils/helpers');

const MAGIC_AI_PROMPT_URL = '/admin/magic-ai/prompts';

const suggestions = (page) => page.locator('.tribute-container li');

async function createPrompt(page, title) {
  await page.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await page.getByRole('button', { name: 'Create Prompt' }).click();
  await expect(page.locator('#app').getByText('Create New Prompt')).toBeVisible();
  await page.locator('input[name="title"]').fill(title);
  await page.locator('textarea[name="prompt"]').fill('Describe the product');
  await clickSave(page, 'Save Prompt');
  await expect(page.locator('#app').getByText('Prompt saved successfully.')).toBeVisible();
}

async function searchPrompt(page, title) {
  await page.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
  await page.getByRole('textbox', { name: 'Search', exact: true }).fill(title);
  await page.keyboard.press('Enter');
  await page.waitForLoadState('networkidle');
}

async function openEdit(page, title) {
  await searchPrompt(page, title);
  await page.locator('div', { hasText: title }).locator('span[title="Edit"]').first().click();
  await expect(page).toHaveURL(/\/admin\/magic-ai\/edit\/\d+/);
}

async function deletePrompt(page, title) {
  await searchPrompt(page, title);

  const remove = page.locator('div', { hasText: title }).locator('span[title="delete"]').first();

  if (await remove.isVisible().catch(() => false)) {
    await remove.click();
    await page.getByRole('button', { name: 'Delete' }).click();
    await page.waitForLoadState('networkidle');
  }
}

async function typeAtEnd(page, text) {
  const prompt = page.locator('textarea[name="prompt"]');
  await prompt.click();
  await prompt.press('Control+End');
  await page.keyboard.type(text, { delay: 50 });
}

/**
 * The prompt field offers "@" attribute suggestions on both the create modal
 * and the edit page, through the shared magic-ai prompt input.
 */
test.describe('Magic AI prompt — attribute suggestions', () => {
  test('edit: typing @ suggests attributes and the pick is saved', async ({ adminPage }) => {
    const page = adminPage;
    const title = `PW Suggest ${generateUid()}`;

    await createPrompt(page, title);

    try {
      await openEdit(page, title);
      await typeAtEnd(page, ' @na');

      const name = suggestions(page).filter({ hasText: /^Name$/ }).first();
      await expect(name).toBeVisible();
      await name.click();

      await expect(page.locator('textarea[name="prompt"]')).toHaveValue(/@name/);

      await clickSave(page, 'Save Prompt');
      await page.reload({ waitUntil: 'networkidle' });

      await expect(page.locator('textarea[name="prompt"]')).toHaveValue(/Describe the product @name/);
    } finally {
      await deletePrompt(page, title);
    }
  });

  test('edit: the @ button opens suggestions and Category switches to category fields', async ({ adminPage }) => {
    const page = adminPage;
    const title = `PW Suggest ${generateUid()}`;

    await createPrompt(page, title);

    try {
      await openEdit(page, title);

      await page.getByRole('button', { name: 'Insert attribute' }).click();
      await expect(suggestions(page).first()).toBeVisible();
      await page.keyboard.press('Escape');

      await page.locator('div').filter({ hasText: /^Product$/ }).nth(1).click();
      await page.getByRole('option', { name: 'Category' }).first().click();

      await typeAtEnd(page, ' @na');

      await expect(suggestions(page).filter({ hasText: /^Name$/ }).first()).toBeVisible();
      await expect(suggestions(page).filter({ hasText: 'Manage Stock' })).toHaveCount(0);
    } finally {
      page.once('dialog', (dialog) => dialog.accept());
      await deletePrompt(page, title);
    }
  });

  test('create: typing @ and the @ button suggest attributes', async ({ adminPage }) => {
    const page = adminPage;

    await page.goto(MAGIC_AI_PROMPT_URL, { waitUntil: 'networkidle' });
    await page.getByRole('button', { name: 'Create Prompt' }).click();
    await expect(page.locator('#app').getByText('Create New Prompt')).toBeVisible();

    await page.getByRole('button', { name: 'Insert attribute' }).click();
    await expect(page.locator('textarea[name="prompt"]')).toHaveValue(/ @$/);
    await expect(suggestions(page).first()).toBeVisible();

    await page.keyboard.type('na', { delay: 50 });
    await expect(suggestions(page).filter({ hasText: /^Name$/ }).first()).toBeVisible();
  });
});
