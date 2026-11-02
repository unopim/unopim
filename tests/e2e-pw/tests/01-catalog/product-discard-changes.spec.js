const path = require('path');
const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo, generateUid, searchInDataGrid, clickEditOnRow, closeDropdown } = require('../../utils/helpers');
const { deleteProductBySku } = require('../../utils/product-helpers');
const { xsrfToken } = require('../../utils/category-field-helpers');
const {
  createGalleryFamily: copyDefaultFamily,
  deleteFamilyByCode,
  assignAttributeToMediaGroup,
  deleteAttributeByCode,
  ensureMediaGroupOpen,
  fieldGroup,
} = require('../../utils/gallery-helpers');

// Regression: Discard must revert rich fields (WYSIWYG/Select/Multiselect/Image/Gallery/File)
// whose value lives in Vue state; each now restores itself on the bar's `unsaved-changes:reset`.

const bar = (page) => page.getByText('You have unsaved changes');

async function confirmDiscard(page) {
  /**
   * The bar slides in over a 200ms CSS transition (`.unsaved-bar-enter-active`
   * in app.css); `force: true` skips Playwright's own actionability retries and
   * clicks at whatever coordinate the button is mid-flight, which is often still
   * off-screen (`bottom: 0` + an in-flight `translateY`) — a genuine "outside of
   * viewport" failure, not a stale-selector issue. A plain click retries until the
   * transition settles, which resolves well inside the default timeout.
   */
  await page.getByRole('button', { name: 'Discard' }).click();
  await page.locator('button.danger-button').first().click().catch(() => {});
}

async function createSimpleProduct(adminPage, sku, familyName) {
  await navigateTo(adminPage, 'products');
  await adminPage.getByRole('button', { name: 'Create Product', exact: true }).click();
  await adminPage.waitForLoadState('networkidle');
  await selectFirstOption(adminPage, 'type', 'Simple');
  await selectFirstOption(adminPage, 'attribute_family_id', familyName);

  await adminPage.locator('input[name="sku"]').fill(sku);
  await clickSave(adminPage, 'Save Product');
  await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\//, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await adminPage.waitForLoadState('networkidle').catch(() => {});
}

/**
 * Create a multiselect attribute carrying one option, through the same
 * endpoints the attribute pages post to.
 */
async function createMultiselectAttribute(page, code, label) {
  const headers = async () => ({ 'X-XSRF-TOKEN': await xsrfToken(page.context()) });

  if (! (await xsrfToken(page.context()))) {
    await navigateTo(page, 'attributes');
  }

  const created = await page.request.post('/admin/catalog/attributes/create', {
    headers: await headers(),
    form: { code, type: 'multiselect', swatch_type: 'text', 'en_US[name]': label },
  });
  const id = created.url().match(/attributes\/edit\/(\d+)/)?.[1];

  expect(id, `creating attribute ${code}: HTTP ${created.status()} at ${created.url()}`).toBeTruthy();

  const option = await page.request.post(`/admin/catalog/attributes/${id}/options/create`, {
    headers: { ...(await headers()), Accept: 'application/json' },
    data: { code: `${code}_opt`, locales: { en_US: { label: `${label} option` } } },
  });

  expect(option.ok(), `creating an option for ${code}: HTTP ${option.status()}`).toBe(true);
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
  await closeDropdown(page);
}

test.describe('Product edit — Discard reverts rich fields', () => {
  test('multiselect edit is reverted by Discard', async ({ adminPage }) => {
    // Demo families carry no multiselect with options, so the test brings one:
    // its own attribute, on a copy of the default family.
    const uid = generateUid();
    const attributeCode = `discard_ms_${uid}`;
    const label = `Discard MS ${uid}`;
    const familyCode = `discard_ms_fam_${uid}`;
    const sku = `discard-ms-${uid}`;

    let family = null;

    try {
      await createMultiselectAttribute(adminPage, attributeCode, label);
      family = await copyDefaultFamily(adminPage, familyCode);
      await assignAttributeToMediaGroup(adminPage, attributeCode, family.id);

      await createSimpleProduct(adminPage, sku, familyCode);
      await ensureMediaGroupOpen(adminPage);

      const msWrapper = fieldGroup(adminPage, label).locator('.multiselect').first();
      await expect(msWrapper).toBeVisible();

      const hidden = msWrapper.locator('xpath=following-sibling::input[@type="hidden"]').first();
      const original = await hidden.inputValue().catch(() => '');

      await msWrapper.locator('.multiselect__tags').click();
      await msWrapper
        .locator('.multiselect__element:not(.multiselect__element--disabled) .multiselect__option:not(.multiselect__option--disabled)')
        .first()
        .click();
      await closeDropdown(adminPage);

      await expect(bar(adminPage)).toBeVisible({ timeout: 10000 });

      await confirmDiscard(adminPage);

      await expect(bar(adminPage)).toBeHidden({ timeout: 10000 });
      await expect(hidden).toHaveValue(original);
    } finally {
      await deleteProductBySku(adminPage, sku);

      if (family) {
        await deleteFamilyByCode(adminPage, familyCode);
      }

      await deleteAttributeByCode(adminPage, attributeCode);
    }
  });
});

// Needs the DiscardQaFixtureSeeder fixture (family e2e_media_qa, product E2E-MEDIA-QA-001). Run first:
//   docker exec unopim-unopim-fpm-1 php artisan db:seed --class=DiscardQaFixtureSeeder
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

    // Drive TinyMCE via its API; typing into the iframe is timing-sensitive and can silently no-op.
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
