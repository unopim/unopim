const path = require('path');
const { test, expect } = require('../../utils/fixtures');
const { navigateTo, clickSave, generateUid, searchInDataGrid } = require('../../utils/helpers');

/**
 * Media download button and read-only locked media fields on product edit.
 */

const IMAGE_FIXTURE = path.resolve(__dirname, '../../utils/berlin.jpeg');
const FAMILY_ID = 1; // `default` — guaranteed on any install, mirrors product-variant-structure.spec.js
const STRUCTURE_CODE = 'e2e_media_download_color_structure';
const STRUCTURE_NAME = 'Media Download E2E Structure';
const AXIS_VALUE = 'Red'; // seed `color` option; its code equals its label (see api/14-variants/variants.spec.js)

/** Fill a TinyMCE editor by textarea ID and sync it for VeeValidate. */
async function fillTinyMCE(page, editorId, text) {
  const iframe = page.locator(`#${editorId}_ifr`);
  await iframe.scrollIntoViewIfNeeded();
  await iframe.waitFor({ state: 'visible', timeout: 10000 });
  const frame = page.frameLocator(`#${editorId}_ifr`);
  await frame.locator('body[contenteditable="true"]').waitFor({ state: 'visible', timeout: 10000 });
  await frame.locator('body').click();
  await page.keyboard.type(text);
  await page.evaluate((id) => {
    const editor = tinymce.get(id);
    if (editor) {
      editor.fire('change');
      editor.save();
    }
  }, editorId);
}

/** Select a value from a Vue-multiselect dropdown by field name. */
async function selectMultiselect(page, fieldName, optionLabel) {
  const wrapper = page.locator(`input[name="${fieldName}"]`)
    .locator('xpath=ancestor::div[contains(concat(" ", normalize-space(@class), " "), " multiselect ")][1]');
  await wrapper.locator('.multiselect__tags').click();
  await wrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
  if (optionLabel) {
    await wrapper.locator(`input[name="${fieldName}"][type="text"]`).fill(optionLabel).catch(() => {});
    await page.getByRole('option', { name: optionLabel }).first().click();
  } else {
    await wrapper
      .locator('.multiselect__element:not(.multiselect__element--disabled) .multiselect__option:not(.multiselect__option--disabled)')
      .first()
      .click();
  }
  await page.keyboard.press('Escape');
}

/**
 * Ensure the `default` family carries a single-axis (Color) variant structure,
 * preserving any structures other specs already created.
 */
async function ensureColorVariantStructure(adminPage) {
  const result = await adminPage.evaluate(async ({ familyId, code, name }) => {
    const xsrf = decodeURIComponent((document.cookie.match(/XSRF-TOKEN=([^;]+)/) || [])[1] || '');
    const headers = {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
      'X-XSRF-TOKEN': xsrf,
    };

    const listRes = await fetch(`/admin/catalog/attribute-families/edit/${familyId}/variant-structures`, { headers });
    const listBody = await listRes.json();
    const existing = listBody.data ?? [];

    if (existing.some((structure) => structure.code === code)) {
      return { status: 200, body: 'already exists' };
    }

    const structures = [
      ...existing.map(({ code: c, name: n, levels, axes, placements }) => ({ code: c, name: n, levels, axes, placements })),
      {
        code,
        name,
        levels: 1,
        axes: { level_1: ['color'] },
        placements: {},
      },
    ];

    const res = await fetch(`/admin/catalog/attribute-families/edit/${familyId}/variant-structures`, {
      method: 'POST',
      headers,
      body: JSON.stringify({ _method: 'PUT', structures }),
    });

    return { status: res.status, body: await res.text() };
  }, { familyId: FAMILY_ID, code: STRUCTURE_CODE, name: STRUCTURE_NAME });

  if (result.status >= 300) {
    throw new Error(`variant structure setup failed: ${result.body}`);
  }
}

async function deleteProductBySku(adminPage, sku) {
  await navigateTo(adminPage, 'products');
  await searchInDataGrid(adminPage, sku);
  const deleteIcon = adminPage.locator('span[title="Delete"]').first();
  const visible = await deleteIcon.isVisible({ timeout: 3000 }).catch(() => false);
  if (!visible) return;
  await deleteIcon.click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await adminPage.locator('#app').getByText(/Product deleted successfully/i).waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
}

/** The `media` attribute-group accordion panel. */
function mediaGroup(page) {
  return page.locator('[data-attribute-group="media"]');
}

/** Expand the group defensively. */
async function ensureMediaGroupOpen(page) {
  const group = mediaGroup(page);
  await group.waitFor({ state: 'attached', timeout: 20000 });
  const header = group.getByRole('button', { name: 'Media', exact: true });
  const expanded = await header.getAttribute('aria-expanded').catch(() => null);
  if (expanded === 'false') {
    await header.click();
  }
  await expect(group.locator('label').filter({ hasText: 'Add Image' }).first().or(group.locator('.group.relative').first()))
    .toBeVisible({ timeout: 10000 });
}

test.describe('Media download button and read-only locked media fields', () => {
  test('owned image field has full hover actions and a live add-tile; a locked (inherited) field on a variant child is read-only, download-only, and its add-tile is inert', async ({ adminPage }) => {
    test.setTimeout(180000);

    const parentSku = `mdl-cfg-${generateUid()}`;
    const childSku = `${parentSku}-${AXIS_VALUE.toLowerCase()}`;

    await navigateTo(adminPage, 'products');
    await ensureColorVariantStructure(adminPage);
    await adminPage.getByRole('button', { name: 'Create Product' }).click();
    await adminPage.waitForLoadState('networkidle');

    await selectMultiselect(adminPage, 'type', 'Configurable');
    await selectMultiselect(adminPage, 'attribute_family_id', 'Default');
    await adminPage.locator('input[name="sku"]').fill(parentSku);

    const createModal = adminPage.locator('.fixed').filter({ hasText: 'Create New Product' }).first();
    await createModal.getByRole('button', { name: 'Next', exact: true }).click();

    await expect(adminPage.locator('#app').getByText('Variant Structure').first()).toBeVisible({ timeout: 10000 });
    await selectMultiselect(adminPage, 'variant_structure_id', STRUCTURE_NAME);
    await adminPage.getByRole('button', { name: 'Save Product', exact: true }).click();

    await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\/(\d+)/, { waitUntil: 'domcontentloaded', timeout: 30000 });
    await adminPage.waitForLoadState('networkidle').catch(() => {});
    const parentId = Number(adminPage.url().match(/\/edit\/(\d+)/)[1]);
    const parentEditUrl = `/admin/catalog/products/edit/${parentId}`;

    await ensureMediaGroupOpen(adminPage);

    await test.step('owned, empty image field: add-tile is live', async () => {
      const addTile = mediaGroup(adminPage).locator('label').filter({ hasText: 'Add Image' }).first();
      await expect(addTile).toBeVisible();
      await expect(addTile.locator('input[type="file"]')).toHaveCount(1);

      const cursor = await addTile.evaluate((el) => getComputedStyle(el).cursor);
      expect(cursor).toBe('pointer');
    });

    let childId;
    await test.step('create a variant child under the color axis', async () => {
      await adminPage.getByRole('button', { name: /Select Color/i }).click();
      await adminPage.getByRole('button', { name: 'Add New', exact: true }).click();

      const addModal = adminPage.locator('.fixed').filter({ hasText: /Add a new Color/i }).first();
      await addModal.waitFor({ state: 'visible', timeout: 10000 });

      await addModal.locator('.multiselect__tags').first().click();
      await addModal.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
      await addModal.locator('.multiselect__option', { hasText: AXIS_VALUE }).first().click();

      const createButton = addModal.getByRole('button', { name: 'Create', exact: true });
      await expect(createButton).toBeEnabled({ timeout: 5000 });
      await createButton.click();

      await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\/(\d+)/, { waitUntil: 'domcontentloaded', timeout: 30000 });
      await adminPage.waitForLoadState('networkidle').catch(() => {});
      childId = Number(adminPage.url().match(/\/edit\/(\d+)/)[1]);
      expect(childId).not.toBe(parentId);
    });

    await test.step('locked, empty image field on the child: add-tile is inert', async () => {
      await ensureMediaGroupOpen(adminPage);

      const addTile = mediaGroup(adminPage).locator('label').filter({ hasText: 'Add Image' }).first();
      await expect(addTile).toBeVisible();

      expect(await addTile.getAttribute('for')).toBeNull();
      await expect(addTile.locator('input[type="file"]')).toHaveCount(0);

      const cursor = await addTile.evaluate((el) => getComputedStyle(el).cursor);
      expect(cursor).toBe('not-allowed');

      const styleBefore = await addTile.evaluate((el) => {
        const s = getComputedStyle(el);
        return { bg: s.backgroundColor, border: s.borderColor };
      });

      await addTile.hover();

      const styleAfter = await addTile.evaluate((el) => {
        const s = getComputedStyle(el);
        return { bg: s.backgroundColor, border: s.borderColor };
      });

      expect(styleAfter).toEqual(styleBefore);
    });

    await test.step('upload an image on the owning (parent) product', async () => {
      await adminPage.goto(parentEditUrl, { waitUntil: 'domcontentloaded' });
      await adminPage.waitForLoadState('networkidle').catch(() => {});
      await ensureMediaGroupOpen(adminPage);

      const addTile = mediaGroup(adminPage).locator('label').filter({ hasText: 'Add Image' }).first();
      await addTile.locator('input[type="file"]').setInputFiles(IMAGE_FIXTURE);

      await adminPage.locator('#name').fill(`Media DL Parent ${parentSku}`);
      await adminPage.locator('#url_key').fill(`media-dl-parent-${parentSku}`);
      const priceInputs = adminPage.locator('[id^="price_"], #price');
      const priceCount = await priceInputs.count();
      for (let i = 0; i < priceCount; i++) {
        await priceInputs.nth(i).fill('100');
      }
      await fillTinyMCE(adminPage, 'short_description', 'Media download E2E fixture');
      await fillTinyMCE(adminPage, 'description', 'Media download E2E fixture');

      await clickSave(adminPage, 'Save Product');
      await expect(adminPage.locator('#app').getByText(/Product updated successfully/i)).toBeVisible({ timeout: 20000 });
    });

    await test.step('owned tile hover reveals preview, replace, delete and download', async () => {
      await adminPage.reload({ waitUntil: 'domcontentloaded' });
      await adminPage.waitForLoadState('networkidle').catch(() => {});
      await ensureMediaGroupOpen(adminPage);

      const card = mediaGroup(adminPage).locator('.group.relative').first();
      await card.hover();

      await expect(card.getByRole('button', { name: 'Preview image' })).toBeVisible();
      await expect(card.getByRole('button', { name: 'Replace image' })).toBeVisible();
      await expect(card.getByRole('button', { name: 'Delete image' })).toBeVisible();

      const download = card.getByRole('link', { name: 'Download' });
      await expect(download).toBeVisible();

      const href = await download.getAttribute('href');
      expect(href).toContain('/admin/media/download?path=');
    });

    await test.step('locked tile hover reveals only preview and download; the download works', async () => {
      await adminPage.goto(`/admin/catalog/products/edit/${childId}`, { waitUntil: 'domcontentloaded' });
      await adminPage.waitForLoadState('networkidle').catch(() => {});
      await ensureMediaGroupOpen(adminPage);

      const card = mediaGroup(adminPage).locator('.group.relative').first();
      await card.hover();

      await expect(card.getByRole('button', { name: 'Preview image' })).toBeVisible();
      await expect(card.getByRole('button', { name: 'Replace image' })).toHaveCount(0);
      await expect(card.getByRole('button', { name: 'Delete image' })).toHaveCount(0);
      await expect(card.locator('.icon-drag')).toHaveCount(0);

      const download = card.getByRole('link', { name: 'Download' });
      await expect(download).toBeVisible();

      const href = await download.getAttribute('href');
      expect(href).toContain('/admin/media/download?path=');

      const storedPath = decodeURIComponent(new URL(href, adminPage.url()).searchParams.get('path'));
      const expectedFilename = storedPath.split('/').pop();

      const [downloadEvent, response] = await Promise.all([
        adminPage.waitForEvent('download'),
        adminPage.waitForResponse((res) => res.url().includes('/admin/media/download')),
        download.click(),
      ]);

      expect(response.ok()).toBeTruthy();
      expect(downloadEvent.suggestedFilename()).toBe(expectedFilename);
    });

    await deleteProductBySku(adminPage, childSku);
    await deleteProductBySku(adminPage, parentSku);
  });
});
