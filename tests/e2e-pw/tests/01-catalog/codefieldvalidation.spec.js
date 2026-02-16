const { test, expect } = require('../../utils/fixtures');

test.describe('UnoPim Test cases (Code field validation category)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Categories' }).click();
  await adminPage.getByRole('link', { name: 'Create Category' }).click();
});    
test('check the code field with less than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('input[name="code"]').fill('Playwrightrectoryexistence');
  await adminPage.waitForTimeout(500);
  await adminPage.locator('#name').click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('#name').type('Playwright1', { delay: 100 });
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Category' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText(/Category created successfully/i)).toBeVisible();
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('input[name="code"]').fill('PlaywrightreportfoldernotfoundatthegivenpathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(500);
  await adminPage.locator('#name').click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('#name').type('Playwright2', { delay: 100 });
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Category' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText(/Category created successfully/i)).toBeVisible();
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('input[name="code"]').fill('PlaywrightreportfoldernotfoundatthegivenpdfgfgsdkjjfgathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(500);
  await adminPage.locator('#name').click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('#name').type('Playwright3', { delay: 100 });
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Category' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText(/Category created successfully/i)).toBeVisible();
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('input[name="code"]').fill('165sdfvjaef');
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('#name').click();
  await adminPage.waitForTimeout(500);
  await adminPage.locator('#name').type('Playwright4', { delay: 100 });
  await adminPage.waitForTimeout(500);
  await adminPage.getByRole('button', { name: 'Save Category' }).click();
  await adminPage.waitForTimeout(500);
  await expect(adminPage.getByText(/Category created successfully/i)).toBeVisible();
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('165s@');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.type('   ');
  await adminPage.waitForTimeout(500);
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('code_field@_test');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('@#%^&*!()');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('');
});
});

test.describe('UnoPim Test cases (Code field validation category field)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Category Fields' }).click();
  await adminPage.getByRole('link', { name: 'Create Category Field' }).click();
});     
test('check the code field with less than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('Playwrightrectoryexistence');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.getByText(/Category Field Created Successfully/i)).toBeVisible();
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('PlaywrightreportfoldernotfoundatthegivenpathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.getByText(/Category Field Created Successfully/i)).toBeVisible();
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('PlaywrightreportfoldernotfoundatthegivenpdfgfgsdkjjfgathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.getByText(/Category Field Created Successfully/i)).toBeVisible();
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('input[name="code"]').fill('165sdfvjaef');
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('#type').getByRole('combobox').locator('div').filter({ hasText: 'Select option' }).click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Category Field' }).click();
  await expect(adminPage.getByText(/Category Field Created Successfully/i)).toBeVisible();
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('165s@');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.type('   ');
  await adminPage.waitForTimeout(500);
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('code_field@_test');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('@#%^&*!()');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('');
});
});

test.describe('UnoPim Test cases (Code field validation attribute)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click(); 
});    
test('check the code field with less than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('Playwrightrectoryexistence');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Created Successfully/i)).toBeVisible();
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('PlaywrightreportfoldernotfoundatthegivenpathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Created Successfully/i)).toBeVisible();
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('PlaywrightreportfoldernotfoundatthegivenpdfgfgsdkjjfgathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Created Successfully/i)).toBeVisible();
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('input[name="code"]').fill('165sdfvjaef');
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Created Successfully/i)).toBeVisible();
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('165s@');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.type('   ');
  await adminPage.waitForTimeout(500);
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('code_field@_test');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('@#%^&*!()');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('');
});
});

test.describe('UnoPim Test cases (Code field validation attribute option)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attributes' }).click();    
});       
test('create the Select type attibute', async ({ adminPage }) => {
  await adminPage.getByRole('link', { name: 'Create Attribute' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).click();
  await adminPage.getByRole('textbox', { name: 'Code' }).fill('material');
  await adminPage.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await adminPage.locator('input[name="type"][type="text"]').fill('Select');
  await adminPage.getByRole('option', { name: 'Select' }).locator('span').first().click();
  await adminPage.locator('input[name="en_US[name]"]').click();
  await adminPage.locator('input[name="en_US[name]"]').fill('Material');
  await adminPage.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(adminPage.getByText(/Attribute Created Successfully/i).first()).toBeVisible();
});

test('check the code field with less than 191 character', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByText('Add Row').click();
  await adminPage.locator('input[name="code"]').nth(2).click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').nth(2).fill('Playwrightrectoryexistence');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText(/Attribute Option Created Successfully/i)).toBeVisible();
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByText('Add Row').click();
  await adminPage.locator('input[name="code"]').nth(2).click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').nth(2).fill('PlaywrightreportfoldernotfoundatthegivenpathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText(/Attribute Option Created Successfully/i)).toBeVisible();
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByText('Add Row').click();
  await adminPage.locator('input[name="code"]').nth(2).click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').nth(2).fill('PlaywrightreportfoldernotfoundatthegivenpdfgfgsdkjjfgathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText(/Attribute Option Created Successfully/i)).toBeVisible();
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByText('Add Row').click();
  await adminPage.locator('input[name="code"]').nth(2).click();
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('input[name="code"]').nth(2).fill('165sdfvjaef');
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('input[name="locales.en_US"]').click();
  await adminPage.locator('input[name="locales.en_US"]').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Option' }).click();
  await expect(adminPage.getByText(/Attribute Option Created Successfully/i)).toBeVisible();
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByText('Add Row').click();
  const codeField = adminPage.locator('input[name="code"]').nth(2);
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('165s@');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByText('Add Row').click();
  const codeField = adminPage.locator('input[name="code"]').nth(2);
  await codeField.type('   ');
  await adminPage.waitForTimeout(500);
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByText('Add Row').click();
  const codeField =  adminPage.locator('input[name="code"]').nth(2);
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('code_field@_test');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const itemRow = adminPage.locator('div', { hasText: 'materialMaterial' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await adminPage.getByText('Add Row').click();
  const codeField =  adminPage.locator('input[name="code"]').nth(2);
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('@#%^&*!()');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('');
});
});

test.describe('UnoPim Test cases (Code field validation attribute group)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Groups' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute Group' }).click();     
});    
test('check the code field with less than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('Playwrightrectoryexistence');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.getByText(/Attribute Group Created Successfully/i)).toBeVisible();
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('PlaywrightreportfoldernotfoundatthegivenpathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.getByText(/Attribute Group Created Successfully/i)).toBeVisible();
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('PlaywrightreportfoldernotfoundatthegivenpdfgfgsdkjjfgathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.getByText(/Attribute Group Created Successfully/i)).toBeVisible();
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('input[name="code"]').fill('165sdfvjaef');
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Group' }).click();
  await expect(adminPage.getByText(/Attribute Group Created Successfully/i)).toBeVisible();
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('165s@');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.type('   ');
  await adminPage.waitForTimeout(500);
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('code_field@_test');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('@#%^&*!()');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('');
});
});

test.describe('UnoPim Test cases (Code field validation attribute family)', () => {
test.beforeEach(async ({adminPage}) => {
  await adminPage.getByRole('link', { name: ' Catalog' }).click();
  await adminPage.getByRole('link', { name: 'Attribute Families' }).click();
  await adminPage.getByRole('link', { name: 'Create Attribute Family' }).click();      
});
test('check the code field with less than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('Playwrightrectoryexistence');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright1', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.getByText(/Family created successfully/i)).toBeVisible();
});

test('check the code field with exactly 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('PlaywrightreportfoldernotfoundatthegivenpathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright2', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.getByText(/Family created successfully/i)).toBeVisible();
});

test('check the code field with more than 191 character', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="code"]').fill('PlaywrightreportfoldernotfoundatthegivenpdfgfgsdkjjfgathEnsuretestsrannwithreporterhtmlandthepathiscorrectbeforeuploadingartifactskshbvsvbdfhvbdfhvbsdhfvbsdhfvbdfshvbsdfhvbfdvbvbfhvuyvuvbyutvbfhvjufdvbsj');
  await adminPage.waitForTimeout(300);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright3', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.getByText(/Family created successfully/i)).toBeVisible();
});

test('able to enter the number first in code field', async ({ adminPage }) => {
  await adminPage.locator('input[name="code"]').click();
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('input[name="code"]').fill('165sdfvjaef');
  await adminPage.waitForTimeout(1000);
  await adminPage.locator('input[name="en_US\\[name\\]"]').click();
  await adminPage.locator('input[name="en_US\\[name\\]"]').type('Playwright4', { delay: 100 });
  await adminPage.getByRole('button', { name: 'Save Attribute Family' }).click();
  await expect(adminPage.getByText(/Family created successfully/i)).toBeVisible();
});

test('verify special characters are removed from code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('165s@');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('165s');
});

test('Spaces should be removed automatically in code field', async ({ adminPage }) => {
  const codeField = adminPage.locator('input[name="code"]');
  await codeField.type('   ');
  await adminPage.waitForTimeout(500);
  await expect(codeField).toHaveValue('');
});

test('Check with special character and underscore in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('code_field@_test');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('code_field_test');
});

test('Special characters should be removed automatically in code field', async ({ adminPage }) => {
  const codeField =  adminPage.locator('input[name="code"]');
  await codeField.click();
  await adminPage.waitForTimeout(1000);
  await codeField.type('@#%^&*!()');
  await adminPage.waitForTimeout(1000);
  await expect(codeField).toHaveValue('');
});
});
