const { test, expect } = require('../../utils/fixtures');
const {
  navigateTo,
  generateUid,
  searchInDataGrid,
} = require('../../utils/helpers');

// ---------------------------------------------------------------------------
// Shared helpers
// ---------------------------------------------------------------------------

/**
 * After clicking Save, wait for either the success toast or a page URL change
 * (redirect to the edit/list page).  This avoids flaky failures when the
 * server is slow and the toast disappears before the assertion fires.
 *
 * @param {import('@playwright/test').Page} page
 * @param {RegExp} toastPattern — regex for the success toast text
 * @param {string} startUrl — the page URL before save was clicked
 */
/**
 * Click a save button and verify the save succeeded.  Accepts either a
 * visible success toast OR a URL redirect as proof of success.
 *
 * @param {import('@playwright/test').Page} page
 * @param {import('@playwright/test').Locator} saveButton
 * @param {RegExp} toastPattern
 */
async function clickSaveAndExpectSuccess(page, saveButton, toastPattern) {
  const startUrl = page.url();
  await saveButton.click();
  // Wait for either the success toast or a URL change (redirect after save).
  await Promise.race([
    expect(page.locator('#app').getByText(toastPattern).first())
      .toBeVisible({ timeout: 25000 })
      .catch(() => {}),
    page.waitForURL((url) => url.toString() !== startUrl, { timeout: 25000 })
      .catch(() => {}),
  ]);
  // If still on the same URL, the toast must be visible — assert it.
  if (page.url() === startUrl) {
    await expect(page.locator('#app').getByText(toastPattern).first())
      .toBeVisible({ timeout: 5000 });
  }
}

/**
 * Fill a 250-char string into a code field, blur, then assert the value was
 * truncated to <= 191 characters by the v-code directive.
 */
async function assertTruncation(page, codeField, blurTarget) {
  const uid = generateUid();
  const prefix = `trunc_${uid}_`;
  const longCode = prefix + 'a'.repeat(250 - prefix.length);

  // fill() triggers an input event; the v-code directive listens for it and
  // truncates via setTimeout(100).  After truncation we must re-fire input
  // so Vue/VeeValidate picks up the new DOM value.
  await codeField.fill(longCode);
  // Wait for the directive's setTimeout(100) to execute
  await page.waitForTimeout(200);
  // The directive has now set el.value to the truncated string, but Vue
  // still holds the old value.  Trigger a fresh input event from the
  // (already-truncated) DOM value so Vue's model updates.
  await page.evaluate((el) => {
    const nativeInputValueSetter = Object.getOwnPropertyDescriptor(
      HTMLInputElement.prototype, 'value'
    ).set;
    nativeInputValueSetter.call(el, el.value);
    el.dispatchEvent(new Event('input', { bubbles: true }));
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }, await codeField.elementHandle());
  await blurTarget.click();
  await page.waitForTimeout(200);

  const val = await codeField.inputValue();
  expect(val.length).toBeLessThanOrEqual(191);
}

/**
 * Create a Select-type attribute and return its code.
 * Used by the attribute-option tests so each one is independent.
 */
async function createSelectAttribute(adminPage) {
  const uid = generateUid();
  const code = `selopt_${uid}`;
  await navigateTo(adminPage, 'attributes');
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(code);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('Select');
  await adminPage.getByRole('option', { name: 'Select' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').fill(`SelOpt ${uid}`);
  await clickSaveAndExpectSuccess(
    adminPage,
    adminPage.getByRole('button', { name: 'Save Attribute' }),
    /Attribute Created Successfully/i
  );
  return code;
}

/**
 * Navigate to the edit page of a Select attribute and click "Add Row" to
 * prepare for option creation.  Returns the option code input locator.
 */
async function navigateToOptionForm(adminPage, attrCode) {
  await navigateTo(adminPage, 'attributes');
  await searchInDataGrid(adminPage, attrCode);
  const editBtn = adminPage.locator('div', { hasText: attrCode }).locator('span[title="Edit"]').first();
  await editBtn.click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByText('Add Row').click();
  // The option code field is the last input[name="code"] after "Add Row" click
  return adminPage.locator('input[name="code"]').last();
}

/**
 * Delete a select attribute by code (best-effort cleanup).
 */
async function deleteAttribute(adminPage, code) {
  await navigateTo(adminPage, 'attributes');
  await searchInDataGrid(adminPage, code);
  const deleteBtn = adminPage.locator('div', { hasText: code }).locator('span[title="Delete"]').first();
  if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
    await deleteBtn.click();
    await adminPage.getByRole('button', { name: 'Delete' }).click();
    await adminPage.waitForLoadState('networkidle');
  }
}

// ---------------------------------------------------------------------------
// 1. Category code validation
// ---------------------------------------------------------------------------
test.describe('Code field validation — Category', () => {
  test.beforeEach(async ({ adminPage }) => {
    await navigateTo(adminPage, 'categories');
    await adminPage.getByRole('link', { name: 'Create Category' }).click();
    await adminPage.waitForLoadState('load');
  });

  test('less than 191 characters', async ({ adminPage }) => {
    const uid = generateUid();
    await adminPage.locator('input[name="code"]').fill(`catshort_${uid}`);
    await adminPage.locator('#name').fill('PW Cat Short');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Category' }), /Category created successfully/i);
  });

  test('exactly 191 characters', async ({ adminPage }) => {
    const uid = generateUid();
    const base = `catexact_${uid}_`;
    const code191 = base + 'x'.repeat(191 - base.length);
    await adminPage.locator('input[name="code"]').fill(code191);
    await adminPage.locator('#name').fill('PW Cat Exact');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Category' }), /Category created successfully/i);
  });

  test('more than 191 characters truncated', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    const blurTarget = adminPage.locator('#name');
    await assertTruncation(adminPage, codeField, blurTarget);
    // Still saveable after truncation
    await blurTarget.fill('PW Cat Long');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Category' }), /Category created successfully/i);
  });

  test('number first in code field', async ({ adminPage }) => {
    const uid = generateUid();
    await adminPage.locator('input[name="code"]').fill(`165num_${uid}`);
    await adminPage.locator('#name').fill('PW Cat Num');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Category' }), /Category created successfully/i);
  });

  test('special characters removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('165s@');
    await expect(codeField).toHaveValue('165s');
  });

  test('spaces removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('   ');
    await expect(codeField).toHaveValue('');
  });

  test('special characters removed but underscore kept', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('code_field@_test');
    await expect(codeField).toHaveValue('code_field_test');
  });

  test('all special characters removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('@#%^&*!()');
    await expect(codeField).toHaveValue('');
  });
});

// ---------------------------------------------------------------------------
// 2. Category field code validation
// ---------------------------------------------------------------------------
test.describe('Code field validation — Category Field', () => {
  test.beforeEach(async ({ adminPage }) => {
    await navigateTo(adminPage, 'categoryFields');
    await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
    await adminPage.waitForLoadState('load');
  });

  /** Helper: select type "Text" in the category-field form */
  async function selectTextType(adminPage) {
    await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
    await adminPage.getByRole('option', { name: 'Text' }).first().click();
  }

  test('less than 191 characters', async ({ adminPage }) => {
    const uid = generateUid();
    await adminPage.locator('input[name="code"]').fill(`cfshort_${uid}`);
    await selectTextType(adminPage);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW CF Short');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Category Field' }), /Category Field Created Successfully/i);
  });

  test('exactly 191 characters', async ({ adminPage }) => {
    const uid = generateUid();
    const base = `cfexact_${uid}_`;
    const code191 = base + 'x'.repeat(191 - base.length);
    await adminPage.locator('input[name="code"]').fill(code191);
    await selectTextType(adminPage);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW CF Exact');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Category Field' }), /Category Field Created Successfully/i);
  });

  test('more than 191 characters truncated', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    const blurTarget = adminPage.locator('input[name="en_US\\[name\\]"]');
    await assertTruncation(adminPage, codeField, blurTarget);
    await selectTextType(adminPage);
    await blurTarget.fill('PW CF Long');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Category Field' }), /Category Field Created Successfully/i);
  });

  test('number first in code field', async ({ adminPage }) => {
    const uid = generateUid();
    await adminPage.locator('input[name="code"]').fill(`165cfnum_${uid}`);
    await selectTextType(adminPage);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW CF Num');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Category Field' }), /Category Field Created Successfully/i);
  });

  test('special characters removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('165s@');
    await expect(codeField).toHaveValue('165s');
  });

  test('spaces removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('   ');
    await expect(codeField).toHaveValue('');
  });

  test('special characters removed but underscore kept', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('code_field@_test');
    await expect(codeField).toHaveValue('code_field_test');
  });

  test('all special characters removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('@#%^&*!()');
    await expect(codeField).toHaveValue('');
  });
});

// ---------------------------------------------------------------------------
// 3. Attribute code validation
// ---------------------------------------------------------------------------
test.describe('Code field validation — Attribute', () => {
  test.beforeEach(async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributes');
    await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
    await adminPage.waitForLoadState('load');
  });

  /** Helper: select type "Text" in the attribute form */
  async function selectTextType(adminPage) {
    await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await adminPage.getByRole('option', { name: 'Text' }).first().click();
  }

  test('less than 191 characters', async ({ adminPage }) => {
    const uid = generateUid();
    await adminPage.locator('input[name="code"]').fill(`attrshort_${uid}`);
    await selectTextType(adminPage);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW Attr Short');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute' }), /Attribute Created Successfully/i);
  });

  test('exactly 191 characters', async ({ adminPage }) => {
    const uid = generateUid();
    const base = `attrexact_${uid}_`;
    const code191 = base + 'x'.repeat(191 - base.length);
    await adminPage.locator('input[name="code"]').fill(code191);
    await selectTextType(adminPage);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW Attr Exact');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute' }), /Attribute Created Successfully/i);
  });

  test('more than 191 characters truncated', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    const blurTarget = adminPage.locator('input[name="en_US\\[name\\]"]');
    await assertTruncation(adminPage, codeField, blurTarget);
    await selectTextType(adminPage);
    await blurTarget.fill('PW Attr Long');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute' }), /Attribute Created Successfully/i);
  });

  test('number first in code field', async ({ adminPage }) => {
    const uid = generateUid();
    await adminPage.locator('input[name="code"]').fill(`165attrnum_${uid}`);
    await selectTextType(adminPage);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW Attr Num');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute' }), /Attribute Created Successfully/i);
  });

  test('special characters removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('165s@');
    await expect(codeField).toHaveValue('165s');
  });

  test('spaces removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('   ');
    await expect(codeField).toHaveValue('');
  });

  test('special characters removed but underscore kept', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('code_field@_test');
    await expect(codeField).toHaveValue('code_field_test');
  });

  test('all special characters removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('@#%^&*!()');
    await expect(codeField).toHaveValue('');
  });
});

// ---------------------------------------------------------------------------
// 4. Attribute option code validation
//    Each test creates its own Select attribute for full independence.
// ---------------------------------------------------------------------------
test.describe('Code field validation — Attribute Option', () => {

  test('less than 191 characters', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const attrCode = await createSelectAttribute(adminPage);
    const optCodeField = await navigateToOptionForm(adminPage, attrCode);
    await optCodeField.fill(`optshort_${uid}`);
    await adminPage.locator('input[name="locales.en_US"]').fill('PW Opt Short');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Option' }), /Attribute Option Created Successfully/i);
    await deleteAttribute(adminPage, attrCode);
  });

  test('exactly 191 characters', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const attrCode = await createSelectAttribute(adminPage);
    const optCodeField = await navigateToOptionForm(adminPage, attrCode);
    const base = `optexact_${uid}_`;
    const code191 = base + 'x'.repeat(191 - base.length);
    await optCodeField.fill(code191);
    await adminPage.locator('input[name="locales.en_US"]').fill('PW Opt Exact');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Option' }), /Attribute Option Created Successfully/i);
    await deleteAttribute(adminPage, attrCode);
  });

  test('more than 191 characters truncated', { timeout: 60000 }, async ({ adminPage }) => {
    const attrCode = await createSelectAttribute(adminPage);
    const optCodeField = await navigateToOptionForm(adminPage, attrCode);
    const blurTarget = adminPage.locator('input[name="locales.en_US"]');
    await assertTruncation(adminPage, optCodeField, blurTarget);
    await blurTarget.fill('PW Opt Long');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Option' }), /Attribute Option Created Successfully/i);
    await deleteAttribute(adminPage, attrCode);
  });

  test('number first in code field', { timeout: 60000 }, async ({ adminPage }) => {
    const uid = generateUid();
    const attrCode = await createSelectAttribute(adminPage);
    const optCodeField = await navigateToOptionForm(adminPage, attrCode);
    await optCodeField.fill(`165optnum_${uid}`);
    await adminPage.locator('input[name="locales.en_US"]').fill('PW Opt Num');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Option' }), /Attribute Option Created Successfully/i);
    await deleteAttribute(adminPage, attrCode);
  });

  test('special characters removed', { timeout: 60000 }, async ({ adminPage }) => {
    const attrCode = await createSelectAttribute(adminPage);
    const optCodeField = await navigateToOptionForm(adminPage, attrCode);
    await optCodeField.pressSequentially('165s@');
    await expect(optCodeField).toHaveValue('165s');
    await deleteAttribute(adminPage, attrCode);
  });

  test('spaces removed', { timeout: 60000 }, async ({ adminPage }) => {
    const attrCode = await createSelectAttribute(adminPage);
    const optCodeField = await navigateToOptionForm(adminPage, attrCode);
    await optCodeField.pressSequentially('   ');
    await expect(optCodeField).toHaveValue('');
    await deleteAttribute(adminPage, attrCode);
  });

  test('special characters removed but underscore kept', { timeout: 60000 }, async ({ adminPage }) => {
    const attrCode = await createSelectAttribute(adminPage);
    const optCodeField = await navigateToOptionForm(adminPage, attrCode);
    await optCodeField.pressSequentially('code_field@_test');
    await expect(optCodeField).toHaveValue('code_field_test');
    await deleteAttribute(adminPage, attrCode);
  });

  test('all special characters removed', { timeout: 60000 }, async ({ adminPage }) => {
    const attrCode = await createSelectAttribute(adminPage);
    const optCodeField = await navigateToOptionForm(adminPage, attrCode);
    await optCodeField.pressSequentially('@#%^&*!()');
    await expect(optCodeField).toHaveValue('');
    await deleteAttribute(adminPage, attrCode);
  });
});

// ---------------------------------------------------------------------------
// 5. Attribute group code validation
// ---------------------------------------------------------------------------
test.describe('Code field validation — Attribute Group', () => {
  test.beforeEach(async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributeGroups');
    await adminPage.getByRole('link', { name: 'Create Attribute Group' }).click();
    await adminPage.waitForLoadState('load');
  });

  test('less than 191 characters', async ({ adminPage }) => {
    const uid = generateUid();
    await adminPage.locator('input[name="code"]').fill(`agshort_${uid}`);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW AG Short');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute Group' }), /Attribute Group Created Successfully/i);
  });

  test('exactly 191 characters', async ({ adminPage }) => {
    const uid = generateUid();
    const base = `agexact_${uid}_`;
    const code191 = base + 'x'.repeat(191 - base.length);
    await adminPage.locator('input[name="code"]').fill(code191);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW AG Exact');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute Group' }), /Attribute Group Created Successfully/i);
  });

  test('more than 191 characters truncated', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    const blurTarget = adminPage.locator('input[name="en_US\\[name\\]"]');
    await assertTruncation(adminPage, codeField, blurTarget);
    await blurTarget.fill('PW AG Long');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute Group' }), /Attribute Group Created Successfully/i);
  });

  test('number first in code field', async ({ adminPage }) => {
    const uid = generateUid();
    await adminPage.locator('input[name="code"]').fill(`165agnum_${uid}`);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW AG Num');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute Group' }), /Attribute Group Created Successfully/i);
  });

  test('special characters removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('165s@');
    await expect(codeField).toHaveValue('165s');
  });

  test('spaces removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('   ');
    await expect(codeField).toHaveValue('');
  });

  test('special characters removed but underscore kept', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('code_field@_test');
    await expect(codeField).toHaveValue('code_field_test');
  });

  test('all special characters removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('@#%^&*!()');
    await expect(codeField).toHaveValue('');
  });
});

// ---------------------------------------------------------------------------
// 6. Attribute family code validation
// ---------------------------------------------------------------------------
test.describe('Code field validation — Attribute Family', () => {
  test.beforeEach(async ({ adminPage }) => {
    await navigateTo(adminPage, 'attributeFamilies');
    await adminPage.getByRole('link', { name: 'Create Attribute Family' }).click();
    await adminPage.waitForLoadState('load');
  });

  test('less than 191 characters', async ({ adminPage }) => {
    const uid = generateUid();
    await adminPage.locator('input[name="code"]').fill(`afshort_${uid}`);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW AF Short');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute Family' }), /Family created successfully/i);
  });

  test('exactly 191 characters', async ({ adminPage }) => {
    const uid = generateUid();
    const base = `afexact_${uid}_`;
    const code191 = base + 'x'.repeat(191 - base.length);
    await adminPage.locator('input[name="code"]').fill(code191);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW AF Exact');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute Family' }), /Family created successfully/i);
  });

  test('more than 191 characters truncated', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    const blurTarget = adminPage.locator('input[name="en_US\\[name\\]"]');
    await assertTruncation(adminPage, codeField, blurTarget);
    await blurTarget.fill('PW AF Long');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute Family' }), /Family created successfully/i);
  });

  test('number first in code field', async ({ adminPage }) => {
    const uid = generateUid();
    await adminPage.locator('input[name="code"]').fill(`165afnum_${uid}`);
    await adminPage.locator('input[name="en_US\\[name\\]"]').fill('PW AF Num');
    await clickSaveAndExpectSuccess(adminPage, adminPage.getByRole('button', { name: 'Save Attribute Family' }), /Family created successfully/i);
  });

  test('special characters removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('165s@');
    await expect(codeField).toHaveValue('165s');
  });

  test('spaces removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('   ');
    await expect(codeField).toHaveValue('');
  });

  test('special characters removed but underscore kept', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('code_field@_test');
    await expect(codeField).toHaveValue('code_field_test');
  });

  test('all special characters removed', async ({ adminPage }) => {
    const codeField = adminPage.locator('input[name="code"]');
    await codeField.pressSequentially('@#%^&*!()');
    await expect(codeField).toHaveValue('');
  });
});
