const path = require('path');
const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo, generateUid, fillLocalizedField } = require('../../utils/helpers');
const { gotoTab, assignAttributesToGroup, saveFamilyEdit } = require('../../utils/family-helpers');

/**
 * Reordering gallery media marks the product form dirty.
 */

const FAMILY_ID = 1;
const IMAGE_1 = path.resolve(__dirname, '../../utils/berlin.jpeg');
const IMAGE_2 = path.resolve(__dirname, '../../utils/bikes.jpeg');

test.describe.configure({ timeout: 240_000 });

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

/** Create a Gallery-type attribute and land on its edit page. */
async function createGalleryAttribute(adminPage, code, name) {
  await navigateTo(adminPage, 'attributes');
  await adminPage.getByRole('button', { name: 'Create Attribute' }).click();
  await adminPage.waitForLoadState('networkidle');
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('Gallery');
  await adminPage.getByRole('option', { name: 'Gallery' }).first().click();
  await fillLocalizedField(adminPage, name);
  await Promise.all([
    adminPage.waitForURL(/\/attributes\/edit\//, { timeout: 20000 }),
    clickSave(adminPage, 'Save Attribute'),
  ]);
  await expect(adminPage.locator('#app').getByText('Edit Attribute').first()).toBeVisible();
}

/** Assign an attribute into the family's Media group and persist it. */
async function assignAttributeToMediaGroup(adminPage, code) {
  await gotoTab(adminPage, FAMILY_ID);
  await adminPage.locator('.group_node').first().waitFor({ state: 'visible', timeout: 30000 });
  await assignAttributesToGroup(adminPage, [code], 'Media');
  await saveFamilyEdit(adminPage);
}

/** Delete an attribute by code, safe if already absent. */
async function deleteAttributeByCode(adminPage, code) {
  await navigateTo(adminPage, 'attributes');
  await adminPage.getByRole('textbox', { name: 'Search', exact: true }).fill(code);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('networkidle');
  const deleteBtn = adminPage.locator('div', { hasText: code }).locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.locator('.max-w-\\[400px\\]').getByRole('button', { name: 'Delete', exact: true }).click();
    await adminPage.waitForLoadState('networkidle');
  }
}

/** Delete a product by SKU, safe if already absent. */
async function deleteProductBySku(adminPage, sku) {
  await navigateTo(adminPage, 'products');
  await adminPage.getByPlaceholder('Search').first().fill(sku);
  await adminPage.keyboard.press('Enter');
  await adminPage.waitForLoadState('load');
  const deleteIcon = adminPage.locator('span[title="Delete"]').first();
  const visible = await deleteIcon.isVisible({ timeout: 3000 }).catch(() => false);
  if (!visible) return;
  await deleteIcon.click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await adminPage.locator('#app').getByText(/Product deleted successfully/i).waitFor({ state: 'visible', timeout: 10000 }).catch(() => {});
}

/** Create a Simple product on the default family and land on its edit page. */
async function createSimpleProduct(adminPage, sku) {
  await navigateTo(adminPage, 'products');
  await adminPage.getByRole('button', { name: 'Create Product' }).click();
  await adminPage.waitForLoadState('networkidle');
  await selectMultiselect(adminPage, 'type', 'Simple');
  await selectMultiselect(adminPage, 'attribute_family_id', 'Default');
  await adminPage.locator('input[name="sku"]').fill(sku);
  await clickSave(adminPage, 'Save Product');
  await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\//, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await adminPage.waitForLoadState('networkidle').catch(() => {});
}

/** The Media attribute-group accordion panel. */
function mediaGroupPanel(page) {
  return page.locator('[data-attribute-group="media"]');
}

/** Expand the Media accordion group. */
async function ensureMediaGroupOpen(page) {
  const group = mediaGroupPanel(page);
  await group.waitFor({ state: 'attached', timeout: 20000 });
  const header = group.getByRole('button', { name: 'Media', exact: true });
  const expanded = await header.getAttribute('aria-expanded').catch(() => null);
  if (expanded === 'false') {
    await header.click();
  }
}

/** The control-group wrapper for one attribute field, located by its visible label. */
function fieldGroup(page, label) {
  return page.locator('[data-control-group]').filter({ hasText: label }).first();
}

/** Read the gallery's order-carrying hidden input values in DOM order. */
async function readGalleryOrder(group) {
  const inputs = group.locator('input[type="hidden"]');
  const count = await inputs.count();
  const values = [];
  for (let i = 0; i < count; i++) {
    const value = await inputs.nth(i).getAttribute('value');
    if (value) values.push(value);
  }
  return values;
}

/** Drag the first gallery tile onto the second via its drag handle. */
async function dragFirstTileToSecond(tiles) {
  const toBox = await tiles.nth(1).boundingBox();
  await tiles.first().locator('.icon-drag').dragTo(tiles.nth(1), {
    targetPosition: { x: toBox.width - 5, y: toBox.height / 2 },
  });
}

test.describe('Gallery reorder marks the product form dirty', () => {
  test.use({ viewport: { width: 1920, height: 1080 } });

  test('dragging a gallery image to a new position surfaces the unsaved-changes bar', async ({ adminPage }) => {
    test.setTimeout(240000);

    const uid = generateUid();
    const code = `gallery_e2e_${uid}`;
    const label = `Gallery Reorder QA ${uid}`;
    const sku = `gal-reorder-${uid}`;

    let attributeCreated = false;
    let productCreated = false;

    try {
      await test.step('provision a multi-valued gallery attribute on the Media group', async () => {
        await createGalleryAttribute(adminPage, code, label);
        attributeCreated = true;
        await assignAttributeToMediaGroup(adminPage, code);
      });

      await test.step('create a product and upload two gallery images', async () => {
        await createSimpleProduct(adminPage, sku);
        productCreated = true;

        await ensureMediaGroupOpen(adminPage);

        const group = fieldGroup(adminPage, label);
        await expect(group).toBeVisible({ timeout: 15000 });

        await group.locator('input[type="file"]').first().setInputFiles([IMAGE_1, IMAGE_2]);

        await adminPage.locator('#name').fill(`Gallery Reorder ${sku}`);
        await adminPage.locator('#url_key').fill(`gallery-reorder-${sku}`);
        const priceInputs = adminPage.locator('[id^="price_"], #price');
        const priceCount = await priceInputs.count();
        for (let i = 0; i < priceCount; i++) {
          await priceInputs.nth(i).fill('100');
        }
        await fillTinyMCE(adminPage, 'short_description', 'Gallery reorder E2E fixture');
        await fillTinyMCE(adminPage, 'description', 'Gallery reorder E2E fixture');

        await clickSave(adminPage, 'Save Product');
        await expect(adminPage.locator('#app').getByText(/Product updated successfully/i)).toBeVisible({ timeout: 20000 });
      });

      let beforeOrder;
      let afterOrder;

      await test.step('reload to the persisted state, then drag-reorder the two images', async () => {
        await adminPage.reload({ waitUntil: 'domcontentloaded' });
        await adminPage.waitForLoadState('networkidle').catch(() => {});
        await ensureMediaGroupOpen(adminPage);

        const group = fieldGroup(adminPage, label);
        const tiles = group.locator('.group.relative');
        await expect(tiles).toHaveCount(2, { timeout: 15000 });

        beforeOrder = await readGalleryOrder(group);
        expect(beforeOrder).toHaveLength(2);

        await dragFirstTileToSecond(tiles);

        afterOrder = await readGalleryOrder(group);
      });

      await test.step('the drag actually reordered the images', async () => {
        expect(afterOrder).toHaveLength(2);
        expect(afterOrder).not.toEqual(beforeOrder);
        expect([...afterOrder].sort()).toEqual([...beforeOrder].sort());
      });

      await test.step('the unsaved-changes bar and field badge appear (the regression guard)', async () => {
        await expect(adminPage.getByText('You have unsaved changes')).toBeVisible({ timeout: 10000 });
        await expect(adminPage.getByRole('button', { name: 'Discard' })).toBeVisible();
        await expect(adminPage.getByRole('button', { name: 'Save changes' })).toBeVisible();

        const group = fieldGroup(adminPage, label);
        await expect(group.locator('.unsaved-badge')).toBeVisible({ timeout: 5000 });
        await expect(group.locator('.unsaved-badge')).toHaveText('Unsaved');
      });
    } finally {
      if (productCreated) {
        await deleteProductBySku(adminPage, sku);
      }
      if (attributeCreated) {
        await deleteAttributeByCode(adminPage, code);
      }
    }
  });
});
