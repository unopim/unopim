const { test, expect } = require('../../utils/fixtures');

/** Short unique suffix to avoid code collisions across test runs */
const uid = Date.now().toString(36);

test.describe('UnoPim Test cases (Code field validation category)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Categories' }).click();
  await adminPage.getByRole('link', { name: 'Create Category' }).click();
  await adminPage.waitForLoadState('load');
});
test('check the code field with less than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(`catshort_${uid}`);
  await adminPage.locator('#name').click();
  await adminPage.locator('#name').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category' }).click();
  await expect(adminPage.locator('#app').getByText(/Category created successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  const base = `catexact_${uid}_`;
  const code191 = base + 'x'.repeat(191 - base.length);
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(code191);
  await adminPage.locator('#name').click();
  await adminPage.locator('#name').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category' }).click();
  await expect(adminPage.locator('#app').getByText(/Category created successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  const base = `catlong_${uid}_`;
  const code250 = base + 'x'.repeat(250 - base.length);
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.fill(code250);
  // Wait for v-code directive to truncate (100ms setTimeout)
  await adminPage.waitForTimeout(200);
  await adminPage.locator('#name').click();
  await adminPage.locator('#name').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category' }).click();
  await expect(adminPage.locator('#app').getByText(/Category created successfully/i)).toBeVisible({ timeout: 15000 });
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(`165num_${uid}`);
  await adminPage.locator('#name').click();
  await adminPage.locator('#name').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category' }).click();
  await expect(adminPage.locator('#app').getByText(/Category created successfully/i)).toBeVisible({ timeout: 15000 });
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('165s@');
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.type('   ');
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('code_field@_test');
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('@#%^&*!()');
  await expect(codeField).toHaveValue('');
});
});

test.describe('UnoPim Test cases (Code field validation category field)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
  await adminPage.waitForLoadState('load');
});
test('check the code field with less than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(`cfshort_${uid}`);
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: 'Text' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.locator('#app').getByText(/Category Field Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  const base = `cfexact_${uid}_`;
  const code191 = base + 'x'.repeat(191 - base.length);
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(code191);
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: 'Text' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.locator('#app').getByText(/Category Field Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  const base = `cflong_${uid}_`;
  const code250 = base + 'x'.repeat(250 - base.length);
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.fill(code250);
  await adminPage.waitForTimeout(200);
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: 'Text' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.locator('#app').getByText(/Category Field Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(`165cfnum_${uid}`);
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: 'Text' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.locator('#app').getByText(/Category Field Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('165s@');
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.type('   ');
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('code_field@_test');
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('@#%^&*!()');
  await expect(codeField).toHaveValue('');
});
});

test.describe('UnoPim Test cases (Code field validation attribute)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.waitForLoadState('load');
});
test('check the code field with less than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(`attrshort_${uid}`);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Text' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  const base = `attrexact_${uid}_`;
  const code191 = base + 'x'.repeat(191 - base.length);
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(code191);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Text' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  const base = `attrlong_${uid}_`;
  const code250 = base + 'x'.repeat(250 - base.length);
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.fill(code250);
  await adminPage.waitForTimeout(200);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Text' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(`165attrnum_${uid}`);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Text' }).first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('165s@');
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.type('   ');
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('code_field@_test');
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('@#%^&*!()');
  await expect(codeField).toHaveValue('');
});
});

test.describe('UnoPim Test cases (Code field validation attribute option)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
});
test('create the Select type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(`material_${uid}`);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('Select');
  await adminPage.getByRole('option', { name: 'Select' }).first().click();
  await adminPage.locator('input[name="en_US[name]"]').click();
  await adminPage.locator('input[name="en_US[name]"]').fill(`Material_${uid}`);
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Created Successfully/i).first()).toBeVisible({ timeout: 15000 });
});

test('check the code field with less than 191 character', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: `material_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByText('Add Row').click();
  await adminPage.locator('input[name="code"]').nth(2).click();
  await adminPage.locator('input[name="code"]').nth(2).fill(`optshort_${uid}`);
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Option Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: `material_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByText('Add Row').click();
  const base = `optexact_${uid}_`;
  const code191 = base + 'x'.repeat(191 - base.length);
  await adminPage.locator('input[name="code"]').nth(2).click();
  await adminPage.locator('input[name="code"]').nth(2).fill(code191);
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Option Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: `material_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByText('Add Row').click();
  const base = `optlong_${uid}_`;
  const code250 = base + 'x'.repeat(250 - base.length);
  await adminPage.locator('input[name="code"]').nth(2).click();
  await adminPage.locator('input[name="code"]').nth(2).fill(code250);
  await adminPage.waitForTimeout(200);
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Option Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: `material_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByText('Add Row').click();
  await adminPage.locator('input[name="code"]').nth(2).click();
  await adminPage.locator('input[name="code"]').nth(2).fill(`165optnum_${uid}`);
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Option Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: `material_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByText('Add Row').click();
  const codeField = adminPage.locator('input[name="code"]').nth(2);
  await codeField.click();
  await codeField.type('165s@');
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: `material_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByText('Add Row').click();
  const codeField = adminPage.locator('input[name="code"]').nth(2);
  await codeField.type('   ');
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: `material_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByText('Add Row').click();
  const codeField =  adminPage.locator('input[name="code"]').nth(2);
  await codeField.click();
  await codeField.type('code_field@_test');
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: `material_${uid}` });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.waitForLoadState('load');
  await adminPage.getByText('Add Row').click();
  const codeField =  adminPage.locator('input[name="code"]').nth(2);
  await codeField.click();
  await codeField.type('@#%^&*!()');
  await expect(codeField).toHaveValue('');
});
});

test.describe('UnoPim Test cases (Code field validation attribute group)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute Group' }).click();
  await adminPage.waitForLoadState('load');
});
test('check the code field with less than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(`agshort_${uid}`);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Group Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  const base = `agexact_${uid}_`;
  const code191 = base + 'x'.repeat(191 - base.length);
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(code191);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Group Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  const base = `aglong_${uid}_`;
  const code250 = base + 'x'.repeat(250 - base.length);
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.fill(code250);
  await adminPage.waitForTimeout(200);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Group Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(`165agnum_${uid}`);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.locator('#app').getByText(/Attribute Group Created Successfully/i)).toBeVisible({ timeout: 15000 });
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('165s@');
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.type('   ');
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('code_field@_test');
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('@#%^&*!()');
  await expect(codeField).toHaveValue('');
});
});

test.describe('UnoPim Test cases (Code field validation attribute family)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute Family' }).click();
  await adminPage.waitForLoadState('load');
});
test('check the code field with less than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(`afshort_${uid}`);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.locator('#app').getByText(/Family created successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  const base = `afexact_${uid}_`;
  const code191 = base + 'x'.repeat(191 - base.length);
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(code191);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.locator('#app').getByText(/Family created successfully/i)).toBeVisible({ timeout: 15000 });
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  const base = `aflong_${uid}_`;
  const code250 = base + 'x'.repeat(250 - base.length);
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.fill(code250);
  await adminPage.waitForTimeout(200);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.locator('#app').getByText(/Family created successfully/i)).toBeVisible({ timeout: 15000 });
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.locator('input[name="code"]').fill(`165afnum_${uid}`);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.locator('#app').getByText(/Family created successfully/i)).toBeVisible({ timeout: 15000 });
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('165s@');
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.type('   ');
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('code_field@_test');
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await codeField.type('@#%^&*!()');
  await expect(codeField).toHaveValue('');
});
});
