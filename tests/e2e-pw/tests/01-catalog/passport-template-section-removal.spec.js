const { test, expect } = require('../../utils/fixtures');

const SUFFIX = Date.now().toString().slice(-6);
const TEMPLATE_CODE = `tpl_section_removal_${SUFFIX}`;
const SECTION_NAME = 'Battery Identity';
const FIELD_NAME = 'Unique Battery Identifier';

async function pickInMultiselect(scope, hiddenName, optionLabel) {
  const hidden = scope.locator(`input[type="hidden"][name="${hiddenName}"]`).first();

  await hidden.waitFor({ state: 'attached' });

  const control = hidden.locator('xpath=parent::*').locator('.multiselect').first();

  for (let attempt = 0; attempt < 3; attempt++) {
    await control.click();

    const search = control.locator('input.multiselect__input');

    if (await search.count()) {
      await search.fill(optionLabel.slice(0, 12));
    }

    const option = control.locator('.multiselect__content-wrapper li, .multiselect__element')
      .filter({ hasText: optionLabel })
      .first();

    await option.click({ timeout: 8_000 }).catch(() => {});

    if (await hidden.inputValue().catch(() => '')) {
      await (scope.keyboard ?? scope.page().keyboard).press('Escape');

      return;
    }
  }

  throw new Error(`multiselect "${hiddenName}" never committed "${optionLabel}"`);
}

async function selectByLabel(modal, name, optionLabel) {
  await pickInMultiselect(modal, name, optionLabel);
}

/**
 * Removing a section whose fields are still mapped used to leave those fields
 * pointing at the deleted code: the rows already read "Default section", but the
 * save was rejected as an unknown section and the deletion never persisted.
 */
test.describe.serial('Passport template section removal', () => {
  let templateUrl;

  test('a template with one mapped section is created', async ({ adminPage }) => {
    await adminPage.goto('/admin/catalog/passports/templates', { waitUntil: 'networkidle' });

    await adminPage.getByRole('button', { name: 'Create Template' }).click();

    await adminPage.locator('input[name$="[name]"]').first().fill('Section Removal Template');
    await adminPage.locator('input[name="code"]').fill(TEMPLATE_CODE);

    await Promise.all([
      adminPage.waitForURL(/\/templates\/\d+\/edit$/),
      adminPage.getByRole('button', { name: 'Save Template' }).click(),
    ]);

    templateUrl = new URL(adminPage.url()).pathname;

    await adminPage.getByRole('button', { name: 'Add Section' }).click();
    await adminPage.locator('input[name="draft_section_name"]').fill(SECTION_NAME);
    await adminPage.getByRole('button', { name: 'Done' }).click();

    await expect(adminPage.getByRole('cell', { name: SECTION_NAME })).toBeVisible();

    /**
     * Persist the section now, before opening the field modal. The page's only
     * save control is the fixed unsaved-changes bar (bottom-0, z-[999]), which
     * mounts the moment the section commit dirties the form; on a template with
     * enough fields the field modal's own footer "Done" button (also fixed,
     * centered near viewport-bottom) can land in the same screen region as the
     * bar. Saving here clears the dirty state and unmounts the bar before that
     * modal ever opens, so the two fixed-position controls never compete for
     * the same click.
     */
    await adminPage.getByRole('button', { name: /^Save changes$/ }).click();

    await expect(adminPage.getByText(/updated successfully/i)).toBeVisible({ timeout: 20000 });

    await adminPage.getByRole('button', { name: 'Add Field' }).click();
    await adminPage.locator('input[name="draft_field_name"]').waitFor();
    await adminPage.locator('input[name="draft_field_name"]').fill(FIELD_NAME);

    const modal = adminPage.locator('input[name="draft_field_name"]').locator('xpath=ancestor::*[self::div][3]');

    await pickInMultiselect(modal, 'draft_field_section', SECTION_NAME);
    await pickInMultiselect(modal, 'draft_field_source', 'Fixed value');

    await modal.locator('input[name$="[fixed_value]"], textarea[name$="[fixed_value]"]').first()
      .fill('BATT-0001')
      .catch(() => {});

    await adminPage.getByRole('button', { name: 'Done' }).click();

    await adminPage.getByRole('button', { name: /^Save changes$/ }).click();

    await expect(adminPage.getByText(/updated successfully/i)).toBeVisible({ timeout: 20000 });
  });

  test('removing the section raises no unknown-section error and survives a reload', async ({ adminPage }) => {
    await adminPage.goto(templateUrl, { waitUntil: 'networkidle' });

    const sectionRow = adminPage.locator('tr', { hasText: SECTION_NAME }).first();

    await expect(sectionRow).toBeVisible();

    const sectionCode = (await sectionRow.locator('input[name$="[code]"]').inputValue()).trim();

    await sectionRow.locator('span.icon-delete').click();

    await expect(adminPage.getByText('The selected section does not exist in this template.')).toHaveCount(0);

    await expect(
      adminPage.locator(`input[name^="fields["][name$="[section]"][value="${sectionCode}"]`)
    ).toHaveCount(0);

    await adminPage.getByRole('button', { name: /^Save changes$/ }).click();

    await expect(adminPage.getByText(/updated successfully/i)).toBeVisible({ timeout: 20000 });

    await adminPage.goto(templateUrl, { waitUntil: 'networkidle' });

    await expect(adminPage.getByRole('cell', { name: SECTION_NAME, exact: true })).toHaveCount(0);

    await expect(adminPage.getByText(FIELD_NAME).first()).toBeVisible();
  });
});
