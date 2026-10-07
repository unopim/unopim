const { test, expect } = require('../../utils/fixtures');
const { clickSave, generateUid } = require('../../utils/helpers');
const {
  IMAGE_1,
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
 * Removing gallery media marks the product form dirty.
 */

test.describe.configure({ timeout: 240_000 });

test.describe('Gallery removal marks the product form dirty', () => {
  test.use({ viewport: { width: 1920, height: 1080 } });

  test('deleting the only gallery image surfaces the field badge and the bar', async ({ adminPage }) => {
    const uid = generateUid();
    const code = `gallery_rm_e2e_${uid}`;
    const label = `Gallery Remove QA ${uid}`;
    const sku = `gal-remove-${uid}`;

    let attributeCreated = false;
    let productCreated = false;

    try {
      await test.step('provision a gallery attribute on the Media group', async () => {
        await createGalleryAttribute(adminPage, code, label);
        attributeCreated = true;
        await assignAttributeToMediaGroup(adminPage, code);
      });

      await test.step('create a product with one saved gallery image', async () => {
        await createSimpleProduct(adminPage, sku);
        productCreated = true;

        await ensureMediaGroupOpen(adminPage);

        const group = fieldGroup(adminPage, label);
        await expect(group).toBeVisible({ timeout: 15000 });

        await group.locator('input[type="file"]').first().setInputFiles(IMAGE_1);
        await expect(group.locator('.group.relative')).toHaveCount(1);

        await fillRequiredProductFields(adminPage, `Gallery Remove ${sku}`);

        await clickSave(adminPage, 'Save Product');
        await expect(adminPage.locator('#app').getByText(/Product updated successfully/i)).toBeVisible({ timeout: 20000 });
      });

      await test.step('reload to the persisted state', async () => {
        await adminPage.reload({ waitUntil: 'domcontentloaded' });
        await adminPage.waitForLoadState('networkidle').catch(() => {});
        await ensureMediaGroupOpen(adminPage);

        const group = fieldGroup(adminPage, label);
        await expect(group.locator('.group.relative')).toHaveCount(1, { timeout: 15000 });
        await expect(group.locator('.unsaved-badge')).toBeHidden();
        await expect(adminPage.getByText('You have unsaved changes')).toBeHidden();
      });

      await test.step('deleting the image marks the field dirty (the regression guard)', async () => {
        const group = fieldGroup(adminPage, label);

        await group.getByRole('button', { name: 'Delete image' }).first().click();

        await expect(group.locator('.group.relative')).toHaveCount(0, { timeout: 10000 });

        await expect(adminPage.getByText('You have unsaved changes')).toBeVisible({ timeout: 10000 });
        await expect(adminPage.getByText(/1 section modified/)).toBeVisible({ timeout: 10000 });

        await expect(group.locator('.unsaved-badge')).toBeVisible({ timeout: 10000 });
        await expect(group.locator('.unsaved-badge')).toHaveText('Unsaved');
      });

      await test.step('discarding clears the badge and restores the image', async () => {
        await adminPage.getByRole('button', { name: 'Discard' }).click();
        await adminPage.locator('button.danger-button').first().click().catch(() => {});

        const group = fieldGroup(adminPage, label);
        await expect(adminPage.getByText('You have unsaved changes')).toBeHidden({ timeout: 10000 });
        await expect(group.locator('.unsaved-badge')).toBeHidden({ timeout: 10000 });
        await expect(group.locator('.group.relative')).toHaveCount(1, { timeout: 10000 });
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
