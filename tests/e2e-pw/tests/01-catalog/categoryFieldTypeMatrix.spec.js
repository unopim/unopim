const { test, expect } = require('../../utils/fixtures');
const {
  navigateTo,
  clickSave,
  clickSaveAndExpect,
  searchInDataGrid,
  clickDeleteOnRow,
  confirmDelete,
  generateUid,
} = require('../../utils/helpers');

// Which controls each field type may expose. Input Validation is text-only, and
// never offered on create.
const TYPE_MATRIX = [
  { type: 'Text',        code: 'text',        inputValidation: true,  isUnique: true,  valuePerLocale: true, wysiwyg: false, options: false },
  { type: 'Textarea',    code: 'textarea',    inputValidation: false, isUnique: false, valuePerLocale: true, wysiwyg: true,  options: false },
  { type: 'Boolean',     code: 'boolean',     inputValidation: false, isUnique: false, valuePerLocale: true, wysiwyg: false, options: false },
  { type: 'Select',      code: 'select',      inputValidation: false, isUnique: false, valuePerLocale: true, wysiwyg: false, options: true  },
  { type: 'Multiselect', code: 'multiselect', inputValidation: false, isUnique: false, valuePerLocale: true, wysiwyg: false, options: true  },
  { type: 'Datetime',    code: 'datetime',    inputValidation: false, isUnique: true,  valuePerLocale: true, wysiwyg: false, options: false },
  { type: 'Date',        code: 'date',        inputValidation: false, isUnique: true,  valuePerLocale: true, wysiwyg: false, options: false },
  { type: 'Image',       code: 'image',       inputValidation: false, isUnique: false, valuePerLocale: true, wysiwyg: false, options: false },
  { type: 'File',        code: 'file',        inputValidation: false, isUnique: false, valuePerLocale: true, wysiwyg: false, options: false },
  { type: 'Checkbox',    code: 'checkbox',    inputValidation: false, isUnique: false, valuePerLocale: true, wysiwyg: false, options: true  },
];

// The Input Validation dropdown, located by its control rather than its label.
const validationControl = (page) =>
  page.locator('div[data-control-group]').filter({ has: page.locator('input[name="validation"]') });

// Targets `.multiselect__tags`, present whether or not a type is already chosen.
async function selectType(page, type) {
  await page.locator('input[name="type"]').locator('..').locator('.multiselect__tags').click();

  // Exact label text: the accessible name carries markup, and "Text" also matches "Textarea".
  await page
    .locator('.multiselect__content-wrapper li span span', { hasText: new RegExp(`^${type}$`) })
    .first()
    .click();
}

// Category fields are created from a quick-create modal on the index page.
async function openCreateModal(page) {
  await navigateTo(page, 'categoryFields');
  await page.getByRole('button', { name: 'Create Category Field' }).click();
  await page.locator('input[name="code"]').waitFor({ state: 'visible', timeout: 30000 });
}

// Create a field of the given type and land on its edit page.
async function createField(page, code, type) {
  await openCreateModal(page);
  await page.locator('input[name$="[name]"]').first().fill(`PW ${type} ${code}`);
  await page.locator('input[name="code"]').fill(code);
  await selectType(page, type);
  await clickSaveAndExpect(page, 'Save Category Field', /Category Field Created Successfully/i);
}

async function deleteField(page, code) {
  await navigateTo(page, 'categoryFields');
  await searchInDataGrid(page, code);
  await clickDeleteOnRow(page, code).catch(() => {});
  await confirmDelete(page).catch(() => {});
}

test.describe('Category field — create modal never offers Input Validation', () => {
  test('Input Validation is absent for every field type', async ({ adminPage }) => {
    await openCreateModal(adminPage);

    await expect(validationControl(adminPage)).toHaveCount(0);

    // Absent for every type, including Text.
    for (const { type } of TYPE_MATRIX) {
      await selectType(adminPage, type);
      await expect(validationControl(adminPage), `Input Validation must not render for ${type}`).toHaveCount(0);
    }
  });

  test('a Textarea field can be created without choosing a validation', async ({ adminPage }) => {
    const code = `cfm_ta_${generateUid()}`;

    // Regression: `validation` was required for every type but only rendered for Text.
    await createField(adminPage, code, 'Textarea');
    await expect(adminPage).toHaveURL(/category-fields\/edit/);

    await deleteField(adminPage, code);
  });
});

test.describe('Category field — option changes are tracked and saved', () => {
  for (const type of ['Select', 'Multiselect', 'Checkbox']) {
    test(`${type} raises the unsaved bar for a new option and persists it`, async ({ adminPage }) => {
      const code = `cfo_${type.toLowerCase()}_${generateUid()}`;

      await createField(adminPage, code, type);

      const saveBar = adminPage.getByRole('button', { name: 'Save changes' });
      await expect(saveBar).toHaveCount(0);

      await adminPage.getByText('Add Row', { exact: true }).first().click();
      await adminPage.getByRole('button', { name: 'Save Option' }).waitFor({ state: 'visible', timeout: 15000 });

      const optionCode = 'opt_one';
      await adminPage.locator('input[name="code"]').last().fill(optionCode);
      const labels = adminPage.locator('input[name^="locales."]');
      for (let i = 0; i < await labels.count(); i++) {
        await labels.nth(i).fill('Option One');
      }

      await adminPage.getByRole('button', { name: 'Save Option' }).click();

      // Option rows are Vue-written hidden inputs the tracker cannot see unsignalled.
      await expect(saveBar).toBeVisible();

      await clickSave(adminPage, 'Save Category Field');
      await adminPage.waitForTimeout(2500);

      await adminPage.reload({ waitUntil: 'load' });
      await expect(adminPage.getByText(optionCode).first()).toBeVisible({ timeout: 20000 });

      await deleteField(adminPage, code);
    });
  }

  test('discarding restores an option that was removed', async ({ adminPage }) => {
    const code = `cfo_discard_${generateUid()}`;
    const optionCode = 'opt_keep';

    await createField(adminPage, code, 'Select');

    // Add an option and save it so it exists server-side.
    await adminPage.getByText('Add Row', { exact: true }).first().click();
    await adminPage.getByRole('button', { name: 'Save Option' }).waitFor({ state: 'visible', timeout: 15000 });
    await adminPage.locator('input[name="code"]').last().fill(optionCode);
    const labels = adminPage.locator('input[name^="locales."]');
    for (let i = 0; i < await labels.count(); i++) {
      await labels.nth(i).fill('Keep Me');
    }
    await adminPage.getByRole('button', { name: 'Save Option' }).click();
    await clickSave(adminPage, 'Save Category Field');
    await adminPage.waitForTimeout(2500);
    await adminPage.reload({ waitUntil: 'load' });
    await expect(adminPage.getByText(optionCode).first()).toBeVisible({ timeout: 20000 });

    // Remove it, then discard instead of saving. An already-saved row is hidden
    // and flagged for deletion rather than detached, so assert on visibility.
    await adminPage.locator('span.icon-delete').first().click();
    await expect(adminPage.getByText(optionCode).first()).toBeHidden();

    // The bar's Discard opens the shared confirm modal; its Discard applies it.
    await adminPage.getByRole('button', { name: 'Discard' }).first().click();

    const confirmModal = adminPage.locator('div.max-w-\\[400px\\]').filter({ hasText: 'Discard changes' });
    await confirmModal.getByRole('button', { name: 'Discard' }).click();

    // Discard reverts native inputs; Vue-held option rows must come back too.
    await expect(adminPage.getByText(optionCode).first()).toBeVisible({ timeout: 20000 });

    await deleteField(adminPage, code);
  });
});

test.describe('Category field — edit form control matrix', () => {
  for (const row of TYPE_MATRIX) {
    test(`${row.type} exposes only its applicable controls`, async ({ adminPage }) => {
      const code = `cfm_${row.code}_${generateUid()}`;

      await createField(adminPage, code, row.type);

      const isUnique = adminPage.locator('input#is_unique');
      const valuePerLocale = adminPage.locator('input#value_per_locale');
      // The toggles ship a hidden `0` companion, so match the control itself.
      const wysiwyg = adminPage.locator('input[name="enable_wysiwyg"][value="1"]');

      await expect(validationControl(adminPage)).toHaveCount(row.inputValidation ? 1 : 0);
      await expect(isUnique).toHaveCount(row.isUnique ? 1 : 0);
      await expect(valuePerLocale).toHaveCount(row.valuePerLocale ? 1 : 0);
      await expect(wysiwyg).toHaveCount(row.wysiwyg ? 1 : 0);

      // Both configuration flags must be changeable after creation, not read-only.
      if (row.isUnique) {
        await expect(isUnique).toBeEnabled();
      }

      await expect(valuePerLocale).toBeEnabled();

      const optionsEmptyState = adminPage.getByText('Add Category Field Options');
      await expect(optionsEmptyState).toHaveCount(row.options ? 1 : 0);

      await deleteField(adminPage, code);
    });
  }
});
