import { test, expect } from '@playwright/test';
test.describe('UnoPim Attribute', () => {
test.beforeEach(async ({ page }) => {
   await page.goto('http://127.0.0.1:8000/admin/login');
  await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
  await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
  await page.getByRole('button', { name: 'Sign In' }).click();
  await expect(page).toHaveURL('http://127.0.0.1:8000/admin/dashboard');
});

test('Create attribute with empty code field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('link', { name: 'Create Attribute' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await page.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
});

test('Create attribute with empty Type field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('link', { name: 'Create Attribute' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product_name');
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await page.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(page.getByText('The Type field is required')).toBeVisible();
});

test('Create attribute with empty Code and Type field', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('link', { name: 'Create Attribute' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('');
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await page.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(page.getByText('The Code field is required')).toBeVisible();
  await expect(page.getByText('The Type field is required')).toBeVisible();
});

test('Create attribute', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('link', { name: 'Create Attribute' }).click();
  await page.getByRole('textbox', { name: 'Code' }).click();
  await page.getByRole('textbox', { name: 'Code' }).fill('product_name');
  await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
  await page.getByRole('option', { name: 'Text' }).locator('span').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('Product Name');
  await page.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(page.getByText(/Attribute Created Successfully/i)).toBeVisible();
});

test('should allow attribute search', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('textbox', { name: 'Search' }).click();
  await page.getByRole('textbox', { name: 'Search' }).type('product_name');
  await page.keyboard.press('Enter');
  await expect(page.locator('text=product_name')).toBeVisible();
});

test('should open the filter menu when clicked', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByText('Filter', { exact: true }).click();
  await expect(page.getByText('Apply Filters')).toBeVisible();
});

test('should allow setting items per page', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByRole('button', { name: '' }).click();
  await page.getByText('20', { exact: true }).click();
  await expect(page.getByRole('button', { name: '' })).toContainText('20');
});

test('should perform actions on a category (Edit, Delete)', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = page.locator('div', { hasText: 'product_name' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await expect(page).toHaveURL(/\/admin\/catalog\/attributes\/edit/);
  await page.goBack();
  await itemRow.locator('span[title="Delete"]').first().click();
  await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
});

test('should allow selecting all category with the mass action checkbox', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.click('label[for="mass_action_select_all_records"]');
  await expect(page.locator('#mass_action_select_all_records')).toBeChecked();
});

test('Update attribute', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  const itemRow = page.locator('div', { hasText: 'product_name' });
  await itemRow.locator('span[title="Edit"]').first().click();
  await page.locator('input[name="en_US\\[name\\]"]').click();
  await page.locator('input[name="en_US\\[name\\]"]').fill('prudact nem');
  await page.locator('#is_required').nth(1).click();
  await page.getByRole('button', { name: 'Save Attribute' }).click();
  await expect(page.getByText(/Attribute Updated Successfully/i)).toBeVisible();
});

test('Delete Attribute', async ({ page }) => {
  await page.getByRole('link', { name: ' Catalog' }).click();
  await page.getByRole('link', { name: 'Attributes' }).click();
  await page.getByText('product_nameprudact nem').getByTitle('Delete').click();
  await page.getByRole('button', { name: 'Delete' }).click();
  await expect(page.getByText(/Attribute Deleted Successfully/i)).toBeVisible();
});
});

test.describe('Checkbox Type Attribute Option Grid', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('http://127.0.0.1:8000/admin/login');
        await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
        await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
        await page.getByRole('button', { name: 'Sign In' }).click();
        await expect(page).toHaveURL('http://127.0.0.1:8000/admin/dashboard');
    })

    test('Adding options should not behttp://127.0.0.1:3000/playwright-report/index.html visible while creating the attribute (checkbox type)', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        await page.getByRole('link', { name: 'Create Attribute' }).click();
        await page.getByRole('textbox', { name: 'Code' }).click();
        await page.getByRole('textbox', { name: 'Code' }).click();
        await page.getByRole('textbox', { name: 'Code' }).fill('in_the_box');
        await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
        await page.locator('input[name="type"][type="text"]').fill('checkbox');
        await page.getByRole('option', { name: 'Checkbox' }).locator('span').first().click();
        await page.locator('input[name="en_US[name]"]').click();
        await page.locator('input[name="en_US[name]"]').fill('In the Box');
        await expect(page.getByText('Options', { exact: true })).not.toBeVisible();
        await expect(page.getByText('Add Row')).not.toBeVisible();
    });

    test('create the checkbox type attibute', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        await page.getByRole('link', { name: 'Create Attribute' }).click();
        await page.getByRole('textbox', { name: 'Code' }).click();
        await page.getByRole('textbox', { name: 'Code' }).click();
        await page.getByRole('textbox', { name: 'Code' }).fill('in_the_box');
        await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
        await page.locator('input[name="type"][type="text"]').fill('checkbox');
        await page.getByRole('option', { name: 'Checkbox' }).locator('span').first().click();
        await page.locator('input[name="en_US[name]"]').click();
        await page.locator('input[name="en_US[name]"]').fill('In the Box');
        await page.getByRole('button', { name: 'Save Attribute' }).click();
        await expect(page.getByText(/Attribute Created Successfully/i).first()).toBeVisible();
        await expect(page.getByText('Options', { exact: true })).toBeVisible();
        await expect(page.getByText('Add Row')).toBeVisible();
    });

    test('Edit and add the options in the checkbox type attibute', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'in_the_box' });
        await itemRow.locator('span[title="Edit"]').first().click();
        await expect(page.getByText('Options', { exact: true })).toBeVisible();
        await expect(page.getByText('Add Row')).toBeVisible();
        await page.getByText('Add Row').click();
        await expect(page.getByText('Add Option')).toBeVisible();
        await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
        await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('adapter');
        await page.locator('input[name="locales.en_US"]').click();
        await page.locator('input[name="locales.en_US"]').fill('Adapter');
        await page.getByRole('button', { name: 'Save Option' }).click();
        await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
        await page.getByText('Close').click();
        await page.getByText('Add Row').click();
        await expect(page.getByText('Add Option')).toBeVisible();
        await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
        await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('cable');
        await page.locator('input[name="locales.en_US"]').click();
        await page.locator('input[name="locales.en_US"]').fill('Cable');
        await page.getByRole('button', { name: 'Save Option' }).click();
        await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
        await page.getByText('Close').click();
        await page.getByText('Add Row').click();
        await expect(page.getByText('Add Option')).toBeVisible();
        await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
        await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('instruction_manual');
        await page.locator('input[name="locales.en_US"]').click();
        await page.locator('input[name="locales.en_US"]').fill('Instruction Manual');
        await page.getByRole('button', { name: 'Save Option' }).click();
        await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
        await page.getByText('Close').click();
        await page.getByText('Add Row').click();
        await expect(page.getByText('Add Option')).toBeVisible();
        await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
        await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('phone_cover');
        await page.locator('input[name="locales.en_US"]').click();
        await page.locator('input[name="locales.en_US"]').fill('Phone Cover');
        await page.getByRole('button', { name: 'Save Option' }).click();
        await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
        await page.getByText('Close').click();
    });
    test('check the search bar of attribute options datagrid', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'in_the_box' });
        await itemRow.locator('span[title="Edit"]').first().click();
        await expect(page.getByText('Options', { exact: true })).toBeVisible();
        await expect(page.getByText('Add Row')).toBeVisible();
        await page.getByRole('textbox', { name: 'Search', exact: true }).click();
        await page.getByRole('textbox', { name: 'Search', exact: true }).fill('cable');
        await page.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
        await expect(page.getByText('cableCable')).toBeVisible();
    });

    test('should allow setting items per page', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'in_the_box' });
        await itemRow.locator('span[title="Edit"]').first().click();
        await page.getByRole('button', { name: '' }).click();
        await page.getByText('20', { exact: true }).click();
        await expect(page.getByRole('button', { name: '' })).toContainText('20');
    });

    test('should perform actions on a attribute option (Edit, Delete)', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'in_the_box' });
        await itemRow.locator('span[title="Edit"]').first().click();
        const itemRow1 = page.locator('div', { hasText: 'cableCable' });
        await itemRow1.locator('span[title="Edit"]').first().click();
        await expect(page.getByText('Add Option')).toBeVisible();
        await page.locator('span.icon-cancel.cursor-pointer').click();
        await itemRow1.locator('span[title="Delete"]').first().click();
        await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
    });

    test('Pagination buttons should be visible, enabled, and clickable', async ({ page }) => {
await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'in_the_box' });
        await itemRow.locator('span[title="Edit"]').first().click();
  const paginationSymbols = ['«', '‹', '›', '»'];
  for (const symbol of paginationSymbols) {
    const button = page.getByText(symbol, { exact: true });
    await expect(button).toBeVisible();
    await expect(button).toBeEnabled();
    await button.click();
    await page.waitForTimeout(300);
  }
});

    test('Delete the checkbox type attibute', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'in_the_box' });
        await itemRow.locator('span[title="Delete"]').first().click();
        await page.getByRole('button', { name: 'Delete' }).click();
        await expect(page.getByText('Attribute Deleted Successfully')).toBeVisible();
    });
});


test.describe('Multiselect Type Attribute Options Grid', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();
    await expect(page).toHaveURL('http://127.0.0.1:8000/admin/dashboard');
  })

  test('Adding options should not be visible while creating the attribute (multiselect type)', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    await page.getByRole('link', { name: 'Create Attribute' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('features');
    await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await page.locator('input[name="type"][type="text"]').fill('Multiselect');
    await page.getByRole('option', { name: 'Multiselect' }).locator('span').first().click();
    await page.locator('input[name="en_US[name]"]').click();
    await page.locator('input[name="en_US[name]"]').fill('Features');
    await expect(page.getByText('Options', { exact: true })).not.toBeVisible();
    await expect(page.getByText('Add Row')).not.toBeVisible();
  });

  test('create the multiselect type attibute', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    await page.getByRole('link', { name: 'Create Attribute' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('features');
    await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await page.locator('input[name="type"][type="text"]').fill('Multiselect');
    await page.getByRole('option', { name: 'Multiselect' }).locator('span').first().click();
    await page.locator('input[name="en_US[name]"]').click();
    await page.locator('input[name="en_US[name]"]').fill('Features');
    await page.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(page.getByText(/Attribute Created Successfully/i).first()).toBeVisible();
    await expect(page.getByText('Options', { exact: true })).toBeVisible();
    await expect(page.getByText('Add Row')).toBeVisible();
  });

  test('Edit and add the options in the multiselect type attibute', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    const itemRow = page.locator('div', { hasText: 'featuresFeatures' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(page.getByText('Options', { exact: true })).toBeVisible();
    await expect(page.getByText('Add Row')).toBeVisible();
    await page.getByText('Add Row').click();
    await expect(page.getByText('Add Option')).toBeVisible();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('waterproof');
    await page.locator('input[name="locales.en_US"]').click();
    await page.locator('input[name="locales.en_US"]').fill('Waterproof');
    await page.getByRole('button', { name: 'Save Option' }).click();
    await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
    await page.getByText('Close').click();
    await page.getByText('Add Row').click();
    await expect(page.getByText('Add Option')).toBeVisible();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('bluetooth');
    await page.locator('input[name="locales.en_US"]').click();
    await page.locator('input[name="locales.en_US"]').fill('Bluetooth');
    await page.getByRole('button', { name: 'Save Option' }).click();
    await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
    await page.getByText('Close').click();
    await page.getByText('Add Row').click();
    await expect(page.getByText('Add Option')).toBeVisible();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('rechargeable');
    await page.locator('input[name="locales.en_US"]').click();
    await page.locator('input[name="locales.en_US"]').fill('Rechargeable');
    await page.getByRole('button', { name: 'Save Option' }).click();
    await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
    await page.getByText('Close').click();
    await page.getByText('Add Row').click();
    await expect(page.getByText('Add Option')).toBeVisible();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('charger');
    await page.locator('input[name="locales.en_US"]').click();
    await page.locator('input[name="locales.en_US"]').fill('Charger');
    await page.getByRole('button', { name: 'Save Option' }).click();
    await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
    await page.getByText('Close').click();
  });

  test('check the search bar of attribute options datagrid', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'featuresFeatures' });
        await itemRow.locator('span[title="Edit"]').first().click();
        await expect(page.getByText('Options', { exact: true })).toBeVisible();
        await expect(page.getByText('Add Row')).toBeVisible();
        await page.getByRole('textbox', { name: 'Search', exact: true }).click();
        await page.getByRole('textbox', { name: 'Search', exact: true }).fill('charger');
        await page.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
        await expect(page.getByText('chargerCharger')).toBeVisible();
    });

    test('should allow setting items per page', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'featuresFeatures' });
        await itemRow.locator('span[title="Edit"]').first().click();
        await page.getByRole('button', { name: '' }).click();
        await page.getByText('20', { exact: true }).click();
        await expect(page.getByRole('button', { name: '' })).toContainText('20');
    });

    test('should perform actions on a attribute option (Edit, Delete)', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'featuresfeatures' });
        await itemRow.locator('span[title="Edit"]').first().click();
        const itemRow1 = page.locator('div', { hasText: 'chargerCharger' });
        await itemRow1.locator('span[title="Edit"]').first().click();
        await expect(page.getByText('Add Option')).toBeVisible();
        await page.locator('span.icon-cancel.cursor-pointer').click();
        await itemRow1.locator('span[title="Delete"]').first().click();
        await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
    });

    test('Pagination buttons should be visible, enabled, and clickable', async ({ page }) => {
await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'featuresFeatures' });
        await itemRow.locator('span[title="Edit"]').first().click();
  const paginationSymbols = ['«', '‹', '›', '»'];
  for (const symbol of paginationSymbols) {
    const button = page.getByText(symbol, { exact: true });
    await expect(button).toBeVisible();
    await expect(button).toBeEnabled();
    await button.click();
    await page.waitForTimeout(300);
  }
});

test('Delete the multiselect type attibute', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    const itemRow = page.locator('div', { hasText: 'featuresFeatures' });
    await itemRow.locator('span[title="Delete"]').first().click();
    await page.getByRole('button', {name: 'Delete'}).click();
    await expect(page.getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});


test.describe('Select Type Attribute', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://127.0.0.1:8000/admin/login');
    await page.getByRole('textbox', { name: 'Email Address' }).fill('admin@example.com');
    await page.getByRole('textbox', { name: 'Password' }).fill('admin123');
    await page.getByRole('button', { name: 'Sign In' }).click();
    await expect(page).toHaveURL('http://127.0.0.1:8000/admin/dashboard');
  })

  test('Adding options should not be visible while creating the attribute (select type)', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    await page.getByRole('link', { name: 'Create Attribute' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('material');
    await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await page.locator('input[name="type"][type="text"]').fill('Select');
    await page.getByRole('option', { name: 'Select' }).locator('span').first().click();
    await page.locator('input[name="en_US[name]"]').click();
    await page.locator('input[name="en_US[name]"]').fill('Material');
    await expect(page.getByText('Options', { exact: true })).not.toBeVisible();
    await expect(page.getByText('Add Row')).not.toBeVisible();
  });

  test('create the Select type attibute', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    await page.getByRole('link', { name: 'Create Attribute' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).click();
    await page.getByRole('textbox', { name: 'Code' }).fill('material');
    await page.locator('input[name="type"]').locator('..').locator('.multiselect__placeholder').click();
    await page.locator('input[name="type"][type="text"]').fill('Select');
    await page.getByRole('option', { name: 'Select' }).locator('span').first().click();
    await page.locator('input[name="en_US[name]"]').click();
    await page.locator('input[name="en_US[name]"]').fill('Material');
    await page.getByRole('button', { name: 'Save Attribute' }).click();
    await expect(page.getByText(/Attribute Created Successfully/i).first()).toBeVisible();
    await expect(page.getByText('Options', { exact: true })).toBeVisible();
    await expect(page.getByText('Add Row')).toBeVisible();
  });

  test('Edit and add the options in the Select type attibute', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    const itemRow = page.locator('div', { hasText: 'materialMaterial' });
    await itemRow.locator('span[title="Edit"]').first().click();
    await expect(page.getByText('Options', { exact: true })).toBeVisible();
    await expect(page.getByText('Add Row')).toBeVisible();
    await page.getByText('Add Row').click();
    await expect(page.getByText('Add Option')).toBeVisible();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('cotton');
    await page.locator('input[name="locales.en_US"]').click();
    await page.locator('input[name="locales.en_US"]').fill('Cotton');
    await page.getByRole('button', { name: 'Save Option' }).click();
    await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
    await page.getByText('Close').click();
    await page.getByText('Add Row').click();
    await expect(page.getByText('Add Option')).toBeVisible();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('fabric');
    await page.locator('input[name="locales.en_US"]').click();
    await page.locator('input[name="locales.en_US"]').fill('Fabric');
    await page.getByRole('button', { name: 'Save Option' }).click();
    await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
    await page.getByText('Close').click();
    await page.getByText('Add Row').click();
    await expect(page.getByText('Add Option')).toBeVisible();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('leather');
    await page.locator('input[name="locales.en_US"]').click();
    await page.locator('input[name="locales.en_US"]').fill('Leather');
    await page.getByRole('button', { name: 'Save Option' }).click();
    await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
    await page.getByText('Close').click();
    await page.getByText('Add Row').click();
    await expect(page.getByText('Add Option')).toBeVisible();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').click();
    await page.locator('form').filter({ hasText: 'Add Option' }).getByPlaceholder('Code').fill('polyester');
    await page.locator('input[name="locales.en_US"]').click();
    await page.locator('input[name="locales.en_US"]').fill('Polyester');
    await page.getByRole('button', { name: 'Save Option' }).click();
    await expect(page.getByText('Attribute Option Created Successfully')).toBeVisible();
    await page.getByText('Close').click();
  });

   test('check the search bar of attribute options datagrid', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'materialMaterial' });
        await itemRow.locator('span[title="Edit"]').first().click();
        await expect(page.getByText('Options', { exact: true })).toBeVisible();
        await expect(page.getByText('Add Row')).toBeVisible();
        await page.getByRole('textbox', { name: 'Search', exact: true }).click();
        await page.getByRole('textbox', { name: 'Search', exact: true }).fill('cotton');
        await page.getByRole('textbox', { name: 'Search', exact: true }).press('Enter');
        await expect(page.getByText('cottonCotton')).toBeVisible();
    });

    test('should allow setting items per page', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'materialMaterial' });
        await itemRow.locator('span[title="Edit"]').first().click();
        await page.getByRole('button', { name: '' }).click();
        await page.getByText('20', { exact: true }).click();
        await expect(page.getByRole('button', { name: '' })).toContainText('20');
    });

    test('should perform actions on a attribute option (Edit, Delete)', async ({ page }) => {
        await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'materialMaterial' });
        await itemRow.locator('span[title="Edit"]').first().click();
        const itemRow1 = page.locator('div', { hasText: 'cottonCotton' });
        await itemRow1.locator('span[title="Edit"]').first().click();
        await expect(page.getByText('Add Option')).toBeVisible();
        await page.locator('span.icon-cancel.cursor-pointer').click();
        await itemRow1.locator('span[title="Delete"]').first().click();
        await expect(page.locator('text=Are you sure you want to delete?')).toBeVisible();
    });

    test('Pagination buttons should be visible, enabled, and clickable', async ({ page }) => {
await page.getByRole('link', { name: ' Catalog' }).click();
        await page.getByRole('link', { name: 'Attributes' }).click();
        const itemRow = page.locator('div', { hasText: 'materialMaterial' });
        await itemRow.locator('span[title="Edit"]').first().click();
  const paginationSymbols = ['«', '‹', '›', '»'];
  for (const symbol of paginationSymbols) {
    const button = page.getByText(symbol, { exact: true });
    await expect(button).toBeVisible();
    await expect(button).toBeEnabled();
    await button.click();
    await page.waitForTimeout(300);
  }
});

test('Delete the Select type attibute', async ({ page }) => {
    await page.getByRole('link', { name: ' Catalog' }).click();
    await page.getByRole('link', { name: 'Attributes' }).click();
    const itemRow = page.locator('div', { hasText: 'materialMaterial' });
    await itemRow.locator('span[title="Delete"]').first().click();
    await page.getByRole('button', {name: 'Delete'}).click();
    await expect(page.getByText('Attribute Deleted Successfully')).toBeVisible();
  });
});