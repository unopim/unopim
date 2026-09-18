const path = require('path');
const { expect } = require('@playwright/test');
const { clickSave, navigateTo, fillLocalizedField, closeDropdown } = require('./helpers');
const { gotoTab, assignAttributesToGroup, saveFamilyEdit } = require('./family-helpers');

const FAMILY_ID = 1;

const IMAGE_1 = path.resolve(__dirname, 'berlin.jpeg');
const IMAGE_2 = path.resolve(__dirname, 'bikes.jpeg');
const PDF = path.resolve(__dirname, '../assets/sample.pdf');

const GALLERY_EXTENSIONS = ['gif', 'jfif', 'jif', 'jpeg', 'jpg', 'png', 'psd', 'tif', 'tiff', 'webp', 'bmp', 'mp4', 'webm', 'mkv'];

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
  await closeDropdown(page);
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

/** Fill the required Default-family fields so the product can be saved. */
async function fillRequiredProductFields(adminPage, label) {
  await adminPage.locator('#name').fill(label);
  await adminPage.locator('#url_key').fill(label.toLowerCase().replace(/[^a-z0-9]+/g, '-'));
  const priceInputs = adminPage.locator('[id^="price_"], #price');
  const priceCount = await priceInputs.count();
  for (let i = 0; i < priceCount; i++) {
    await priceInputs.nth(i).fill('100');
  }
  await fillTinyMCE(adminPage, 'short_description', label);
  await fillTinyMCE(adminPage, 'description', label);
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

module.exports = {
  FAMILY_ID,
  IMAGE_1,
  IMAGE_2,
  PDF,
  GALLERY_EXTENSIONS,
  fillTinyMCE,
  selectMultiselect,
  createGalleryAttribute,
  assignAttributeToMediaGroup,
  deleteAttributeByCode,
  deleteProductBySku,
  createSimpleProduct,
  fillRequiredProductFields,
  mediaGroupPanel,
  ensureMediaGroupOpen,
  fieldGroup,
};
