const path = require('path');
const { test, expect } = require('../../utils/fixtures');
const { clickSave, resolveEditableProductId } = require('../../utils/helpers');

const FIRST_IMAGE = path.resolve(__dirname, '../../utils/berlin.jpeg');
const SECOND_IMAGE = path.resolve(__dirname, '../../utils/bikes.jpeg');

function imageField(page) {
  return page
    .locator('[data-control-group]')
    .filter({ has: page.locator('label[for="values_common_image"]') })
    .first();
}

async function openImageField(page) {
  const group = page.locator('[data-attribute-group="media"]');
  await group.waitFor({ state: 'attached', timeout: 20000 });

  const header = group.getByRole('button', { name: 'Media', exact: true });

  if (await header.getAttribute('aria-expanded').catch(() => null) === 'false') {
    await header.click();
  }

  await expect(imageField(page).locator('[data-media-control]')).toBeVisible({ timeout: 15000 });
}

async function setImage(page, file) {
  const field = imageField(page);
  const tile = field.locator('.group.relative').first();

  if (await tile.count() === 0) {
    await field.locator('label[aria-label="Add Image"] input[type="file"]').first().setInputFiles(file);

    return;
  }

  await tile.hover();
  await tile.getByRole('button', { name: 'Replace image' }).click();
  await field.locator('input[type="file"]').last().setInputFiles(file);
}

async function saveProduct(page) {
  await clickSave(page, 'Save Product');
  await expect(page.locator('#app').getByText(/Product updated successfully/i)).toBeVisible({ timeout: 20000 });
}

async function reopen(page, editUrl) {
  await page.goto(editUrl, { waitUntil: 'domcontentloaded' });
  await page.waitForLoadState('networkidle').catch(() => {});
  await openImageField(page);
}

test.describe('Product image replacement', () => {
  test('a replaced image is still shown after the first save', async ({ adminPage }) => {
    test.setTimeout(180000);

    const productId = await resolveEditableProductId(adminPage);
    const editUrl = `/admin/catalog/products/edit/${productId}`;

    await test.step('store a known image on the product', async () => {
      await reopen(adminPage, editUrl);
      await setImage(adminPage, FIRST_IMAGE);
      await saveProduct(adminPage);
    });

    await test.step('the stored image is rendered on reload', async () => {
      await reopen(adminPage, editUrl);

      await expect(imageField(adminPage).locator('img').first()).toHaveAttribute('src', /berlin/, { timeout: 15000 });
    });

    await test.step('replace it with a different file and save once', async () => {
      await setImage(adminPage, SECOND_IMAGE);
      await saveProduct(adminPage);
    });

    await test.step('the replacement is rendered on reload, without a second save', async () => {
      await reopen(adminPage, editUrl);

      const image = imageField(adminPage).locator('img').first();

      await expect(image).toHaveAttribute('src', /bikes/, { timeout: 15000 });
      await expect(image).not.toHaveAttribute('src', /berlin/);
    });
  });
});
