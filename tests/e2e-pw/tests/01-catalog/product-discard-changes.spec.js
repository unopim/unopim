const path = require('path');
const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo, generateUid, searchInDataGrid, clickEditOnRow } = require('../../utils/helpers');

/**
 * Regression: "Discard Changes" must revert rich attribute types whose value
 * lives in Vue state (not the native DOM element) — WYSIWYG, Select, Multiselect,
 * Image, Gallery, File. Before the fix the discard handler only reset native
 * inputs, so these fields kept their edited value and the bar stayed dirty.
 *
 * Each rich field now listens for the `unsaved-changes:reset` broadcast the bar
 * emits on discard and restores its own initial value.
 */

const bar = (page) => page.getByText('You have unsaved changes');

async function confirmDiscard(page) {
  await page.getByRole('button', { name: 'Discard' }).click();
  await page.locator('button.danger-button').first().click().catch(() => {});
}

async function createSimpleProduct(adminPage, sku) {
  await navigateTo(adminPage, 'products');
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.waitForLoadState('networkidle');
  await selectFirstOption(adminPage, 'type', 'Simple');
  await selectFirstOption(adminPage, 'attribute_family_id');
  await adminPage.locator('input[name="sku"]').fill(sku);
  await clickSave(adminPage, 'Save Product');
  await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\//, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await adminPage.waitForLoadState('networkidle').catch(() => {});
}

async function selectFirstOption(page, fieldName, optionLabel) {
  const wrapper = page.locator(`input[name="${fieldName}"]`).locator('..');
  await wrapper.locator('.multiselect__tags').click();
  await wrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
  if (optionLabel) {
    await page.getByRole('option', { name: optionLabel }).first().click();
  } else {
    await wrapper
      .locator('.multiselect__element:not(.multiselect__element--disabled) .multiselect__option:not(.multiselect__option--disabled)')
      .first()
      .click();
  }
  await page.keyboard.press('Escape');
}

async function deleteProductBySku(adminPage, sku) {
  await navigateTo(adminPage, 'products');
  await searchInDataGrid(adminPage, sku);
  const deleteIcon = adminPage.locator('span[title="Delete"]').first();
  if (!(await deleteIcon.isVisible({ timeout: 3000 }).catch(() => false))) {
    return;
  }
  await deleteIcon.click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await adminPage.waitForLoadState('networkidle').catch(() => {});
}

test.describe('Product edit — Discard reverts rich fields', () => {
  test('multiselect edit is reverted by Discard', async ({ adminPage }) => {
    const sku = `discard-ms-${generateUid()}`;
    await createSimpleProduct(adminPage, sku);

    // First multiselect inside the tracked form, excluding the top type/family selects.
    const msWrapper = adminPage
      .locator('.unsaved-root .multiselect')
      .filter({ hasNot: adminPage.locator('input[name="type"], input[name="attribute_family_id"]') })
      .first();

    if (!(await msWrapper.isVisible({ timeout: 5000 }).catch(() => false))) {
      test.skip(true, 'No editable multiselect attribute in this product family');
    }

    const hidden = msWrapper.locator('xpath=following-sibling::input[@type="hidden"]').first();
    const original = await hidden.inputValue().catch(() => '');

    await msWrapper.locator('.multiselect__tags').click();
    await msWrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
    await msWrapper
      .locator('.multiselect__element:not(.multiselect__element--disabled) .multiselect__option:not(.multiselect__option--disabled)')
      .first()
      .click();
    await adminPage.keyboard.press('Escape');

    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    await confirmDiscard(adminPage);

    await expect(bar(adminPage)).toBeHidden({ timeout: 10000 });
    await expect(hidden).toHaveValue(original);

    await deleteProductBySku(adminPage, sku);
  });
});

/**
 * Rich media/WYSIWYG fields covered against a dedicated fixture family+product
 * seeded by database/seeders/DiscardQaFixtureSeeder (attributes e2e_qa_wysiwyg,
 * e2e_qa_image, e2e_qa_gallery, e2e_qa_file on family e2e_media_qa, product
 * E2E-MEDIA-QA-001). Run the seeder before this block:
 *   docker exec unopim-unopim-fpm-1 php artisan db:seed --class=DiscardQaFixtureSeeder
 */
test.describe('Product edit — Discard reverts WYSIWYG/media/file', () => {
  const SKU = 'E2E-MEDIA-QA-001';
  const asset = (name) => path.resolve(__dirname, '../../assets', name);

  async function openFixtureProduct(page) {
    await navigateTo(page, 'products');
    await searchInDataGrid(page, SKU);
    await clickEditOnRow(page, SKU);
    await page.waitForLoadState('networkidle').catch(() => {});
  }

  const fieldGroup = (page, label) =>
    page.locator('[data-control-group]').filter({ hasText: label }).first();

  test('WYSIWYG edit is reverted by Discard', async ({ adminPage }) => {
    await openFixtureProduct(adminPage);

    const textarea = adminPage.locator('textarea[name="values[common][e2e_qa_wysiwyg]"]');
    await textarea.waitFor({ state: 'attached', timeout: 15000 });
    const editorId = await textarea.getAttribute('id');

    const frame = adminPage.frameLocator(`#${editorId}_ifr`);
    await frame.locator('body[contenteditable="true"]').waitFor({ state: 'visible', timeout: 10000 });
    const original = await adminPage.evaluate((id) => window.tinymce.get(id).getContent(), editorId);

    // Drive TinyMCE through its API rather than keyboard input — typing into the
    // iframe is timing-sensitive and can silently no-op; setContent + fire('change')
    // deterministically updates content and triggers the component's dirty tracking.
    await adminPage.evaluate((id) => {
      const editor = window.tinymce.get(id);
      editor.setContent('<p>Discarded WYSIWYG edit</p>');
      editor.fire('input');
      editor.fire('change');
    }, editorId);

    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    await confirmDiscard(adminPage);

    await expect(bar(adminPage)).toBeHidden({ timeout: 10000 });
    await expect
      .poll(async () => adminPage.evaluate((id) => window.tinymce.get(id).getContent(), editorId), { timeout: 8000 })
      .toBe(original);
  });

  for (const [label, code, file] of [
    ['E2E QA Image', 'e2e_qa_image', 'dotted.png'],
    ['E2E QA Gallery', 'e2e_qa_gallery', 'floral.jpg'],
  ]) {
    test(`${label} upload is reverted by Discard`, async ({ adminPage }) => {
      await openFixtureProduct(adminPage);

      const group = fieldGroup(adminPage, label);
      const itemInputs = adminPage.locator(`input[name="values[common][${code}][]"]`);
      await expect(itemInputs).toHaveCount(0);

      await group.locator('input[type="file"]').first().setInputFiles(asset(file));
      await expect(itemInputs).toHaveCount(1, { timeout: 10000 });
      await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

      await confirmDiscard(adminPage);

      await expect(bar(adminPage)).toBeHidden({ timeout: 10000 });
      await expect(itemInputs).toHaveCount(0, { timeout: 10000 });
    });
  }

  test('File upload is reverted by Discard', async ({ adminPage }) => {
    await openFixtureProduct(adminPage);

    const group = fieldGroup(adminPage, 'E2E QA File');
    await group.locator('input[type="file"]').first().setInputFiles(asset('sample.pdf'));

    await expect(group.getByText('sample.pdf')).toBeVisible({ timeout: 10000 });
    await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

    await confirmDiscard(adminPage);

    await expect(bar(adminPage)).toBeHidden({ timeout: 10000 });
    await expect(group.getByText('sample.pdf')).toBeHidden({ timeout: 10000 });
  });
});
