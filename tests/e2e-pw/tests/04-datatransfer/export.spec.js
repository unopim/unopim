const { test, expect } = require('../../utils/fixtures');
const { navigateTo, generateUid } = require('../../utils/helpers');

const uid = generateUid();
const CAT_CSV = `Cat Export CSV ${uid}`;
const CAT_XLS = `Cat Export XLS ${uid}`;
const CAT_XLSX = `Cat Export XLSX ${uid}`;
const PROD_CSV = `Prod Export CSV ${uid}`;
const PROD_XLS = `Prod Export XLS ${uid}`;
const PROD_XLSX = `Prod Export XLSX ${uid}`;

test.describe.serial('UnoPim Export Jobs', () => {
test('Create Export with empty Code field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
});

test('Create Export with empty Type field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(CAT_CSV);
  await adminPage.locator('#export-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
});

test('Create Export with empty File Format field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(CAT_CSV);
  await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText('The File Format field is required')).toBeVisible();
});

test('Create Export with empty Code, Type and File Format field', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('');
  await adminPage.locator('#export-type').getByRole('combobox').locator('div').filter({ hasText: 'Categories' }).click();
  await adminPage.getByRole('option', { name: 'Categories' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText('The Code field is required')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('The Type field is required')).toBeVisible();
  await expect(adminPage.locator('#app').getByText('The File Format field is required')).toBeVisible();
});

test('Create Category Export (CSV)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(CAT_CSV);
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText(/Export created successfully/i)).toBeVisible();
});

test('Create Export with same Code', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(CAT_CSV);
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText('The Code has already been taken.')).toBeVisible();
});

test('should allow Export search', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('textbox', { name: 'Search' }).click();
  await adminPage.getByRole('textbox', { name: 'Search' }).type(CAT_CSV);
  await adminPage.keyboard.press('Enter');
  await expect(adminPage.locator('#app').getByText(CAT_CSV, {exact:true})).toBeVisible();
});

test('should open the filter menu when clicked', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByText('Filter', { exact: true }).click();
  await expect(adminPage.locator('#app').getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per adminPage', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  const perPageBtn = adminPage.getByRole('button', { name: 'Per Page' });
  await perPageBtn.click();
  await adminPage.getByText('20', { exact: true }).click();
  await expect(perPageBtn).toContainText('20');
});

test('should perform actions on a Export job (Edit, Delete)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  const itemRow = adminPage.locator('div', { hasText: CAT_CSV });
  await itemRow.locator('span[title="Export"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/exports\/export/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(adminPage).toHaveURL(/\/admin\/settings\/data-transfer\/exports\/edit/);
  await adminPage.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(adminPage.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('Delete Category Export CSV', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  const itemRow = adminPage.locator('div', { hasText: CAT_CSV });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible();
});

test('Create Category Export (XLS)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(CAT_XLS);
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'XLS' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText(/Export created successfully/i)).toBeVisible();
  await expect(adminPage.getByRole('button', { name: 'Export Now' })).toBeVisible();
  await adminPage.getByRole('button', { name: 'Export Now' }).click();
  await expect(adminPage.locator('#app').getByText('Job queued')).toBeVisible();
});

test('Delete Category Export (XLS)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  const itemRow = adminPage.locator('div', { hasText: CAT_XLS });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible();
});

test('Create Category Export (XLSX)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(CAT_XLSX);
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'XLSX' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText(/Export created successfully/i)).toBeVisible();
});

test('Delete Category Export (XLSX)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  const itemRow = adminPage.locator('div', { hasText: CAT_XLSX });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible();
});

test('Create Product Export (CSV)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(PROD_CSV);
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'CSV' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText(/Export created successfully/i)).toBeVisible();
});

test('Delete Product Export CSV', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  const itemRow = adminPage.locator('div', { hasText: PROD_CSV });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible();
});

test('Create Product Export (XLS)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(PROD_XLS);
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'XLS' }).locator('span').first().click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText(/Export created successfully/i)).toBeVisible();
});

test('Delete Product Export (XLS)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  const itemRow = adminPage.locator('div', { hasText: PROD_XLS });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible();
});

test('Create Product Export (XLSX)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  await adminPage.getByRole('link', { name: 'Create Export' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill(PROD_XLSX);
  await adminPage.locator('input[name="filters[file_format]"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'XLSX' }).locator('span').first().click();
  await adminPage.locator('div').filter({ hasText: /^With Media$/ }).locator('div').click();
  await adminPage.getByRole('button', { name: 'Save Export' }).click();
  await expect(adminPage.locator('#app').getByText(/Export created successfully/i)).toBeVisible();
});

test('Delete Product Export (XLSX)', async ({ adminPage }) => {
  await navigateTo(adminPage, 'exports');
  const itemRow = adminPage.locator('div', { hasText: PROD_XLSX });
  await itemRow.locator('span[title="Delete"]').first().click();
  await adminPage.getByRole('button', { name: 'Delete' }).click();
  await expect(adminPage.locator('#app').getByText(/Export deleted successfully/i)).toBeVisible();
});
});
