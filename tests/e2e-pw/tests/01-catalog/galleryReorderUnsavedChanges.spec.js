const { test, expect } = require('../../utils/fixtures');
const { clickSave, generateUid } = require('../../utils/helpers');
const {
  IMAGE_1,
  IMAGE_2,
  PDF,
  GALLERY_EXTENSIONS,
  createGalleryAttribute,
  assignAttributeToMediaGroup,
  deleteAttributeByCode,
  deleteProductBySku,
  createSimpleProduct,
  fillRequiredProductFields,
  ensureMediaGroupOpen,
  fieldGroup,
} = require('../../utils/gallery-helpers');

/**
 * Reordering gallery media marks the product form dirty.
 */

test.describe.configure({ timeout: 240_000 });

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

        const addTile = group.locator('label.border-dashed');
        await expect(addTile).toHaveAttribute('title', GALLERY_EXTENSIONS.join(', '));

        const picker = group.locator('input[type="file"]').first();
        await expect(picker).toHaveAttribute('accept', GALLERY_EXTENSIONS.map(extension => `.${extension}`).join(','));
        await picker.setInputFiles(PDF);
        await expect(adminPage.getByText('Only image and video files are allowed. (.mp4, .jpg ..)', { exact: true })).toBeVisible();
        await expect(group.locator('.group.relative')).toHaveCount(0);

        await group.locator('input[type="file"]').first().setInputFiles([IMAGE_1, IMAGE_2]);

        await expect(group.locator('.group.relative')).toHaveCount(2);
        const replacementPicker = group.locator('input[type="file"][name]').first();
        await expect(replacementPicker).toHaveAttribute('accept', GALLERY_EXTENSIONS.map(extension => `.${extension}`).join(','));
        await replacementPicker.setInputFiles({
          name: 'gallery.jfif',
          mimeType: 'image/jpeg',
          buffer: require('fs').readFileSync(IMAGE_1),
        });

        await fillRequiredProductFields(adminPage, `Gallery Reorder ${sku}`);

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
