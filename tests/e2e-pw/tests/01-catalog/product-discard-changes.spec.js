const path = require('path');
const { test, expect } = require('../../utils/fixtures');
const { clickSave, navigateTo, generateUid, searchInDataGrid, clickEditOnRow } = require('../../utils/helpers');

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

async function createSimpleProduct(adminPage, sku, familyIndex = 0) {
  await navigateTo(adminPage, 'products');
  await adminPage.getByRole('button', { name: 'Create Product', exact: true }).click();
  await adminPage.waitForLoadState('networkidle');
  await selectFirstOption(adminPage, 'type', 'Simple');

  const familyWrapper = adminPage.locator('input[name="attribute_family_id"]').locator('..');
  await familyWrapper.locator('.multiselect__tags').click();
  await familyWrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });
  await familyWrapper
    .locator('.multiselect__element:not(.multiselect__element--disabled) .multiselect__option:not(.multiselect__option--disabled)')
    .nth(familyIndex)
    .click();
  await adminPage.keyboard.press('Escape');

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

/**
 * Not every family's first non-type/family multiselect necessarily has any
 * selectable option (e.g. a fresh install with no tax categories seeded
 * leaves `tax_category_id` empty), so this opens each candidate in DOM order
 * until it finds one that actually offers something to pick, rather than
 * assuming the very first one does.
 */
async function findMultiselectWithOptions(page) {
  const candidates = page
    .locator('.unsaved-root .multiselect')
    .filter({ hasNot: page.locator('input[name="type"], input[name="attribute_family_id"]') });

  const count = await candidates.count();

  for (let i = 0; i < count; i++) {
    const candidate = candidates.nth(i);

    if (!(await candidate.isVisible({ timeout: 3000 }).catch(() => false))) {
      continue;
    }

    await candidate.locator('.multiselect__tags').click();
    await candidate.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});

    // `isVisible()` is an instant, non-retrying snapshot (unlike `waitFor`/`expect`),
    // so it can race the dropdown's options rendering a tick after the click —
    // `waitFor` actually polls until the option appears or the timeout elapses.
    const hasOption = await candidate
      .locator('.multiselect__element:not(.multiselect__element--disabled) .multiselect__option:not(.multiselect__option--disabled)')
      .first()
      .waitFor({ state: 'visible', timeout: 2000 })
      .then(() => true)
      .catch(() => false);

    if (hasOption) {
      return candidate;
    }

    await page.keyboard.press('Escape');
  }

  return null;
}

test.describe('Product edit — Discard reverts rich fields', () => {
  test('multiselect edit is reverted by Discard', async ({ adminPage }) => {
    // The very first family option (typically `default`) can be a bare fixture
    // family with no populated multiselect attribute of its own (e.g. a fresh
    // install with no tax categories leaves `tax_category_id` optionless) — try
    // a handful of families in listed order until one actually offers a
    // selectable option, rather than assuming the first one does.
    const MAX_FAMILY_ATTEMPTS = 6;

    let msWrapper = null;
    let sku = null;

    for (let familyIndex = 0; familyIndex < MAX_FAMILY_ATTEMPTS; familyIndex++) {
      sku = `discard-ms-${generateUid()}`;
      await createSimpleProduct(adminPage, sku, familyIndex);

      msWrapper = await findMultiselectWithOptions(adminPage);

      if (msWrapper) {
        break;
      }

      await deleteProductBySku(adminPage, sku);
    }

    if (!msWrapper) {
      test.skip(true, `No editable multiselect attribute with a selectable option in the first ${MAX_FAMILY_ATTEMPTS} families`);
    }

    const hidden = msWrapper.locator('xpath=following-sibling::input[@type="hidden"]').first();
    const original = await hidden.inputValue().catch(() => '');

    // The dropdown opened by `findMultiselectWithOptions` while probing is still open.
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
