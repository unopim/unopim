const { test, expect } = require('../../utils/fixtures');
const { gotoPage, clickSave, clickSaveAndExpect, generateUid } = require('../../utils/helpers');
const { createEditableSimpleProduct, deleteProductBySku } = require('../../utils/product-helpers');

const PLAIN = 'meta_title';
const RICH = 'short_description';
const WITHOUT_TEMPLATES = 'meta_description';

const HIDE_OVERLAYS = `
  (function () {
    try { sessionStorage.setItem('agenting_pim_state', JSON.stringify({ isOpen: false })); } catch (e) {}
    var style = document.createElement('style');
    style.textContent = '.ap-shell, .ap-panel, .ap-backdrop, .phpdebugbar { display: none !important; }';
    if (document.head) { document.head.appendChild(style); }
    else { document.addEventListener('DOMContentLoaded', function () { document.head.appendChild(style); }); }
  })();
`;

const shared = {
  sku: `tpl-${generateUid()}`,
  productId: null,
  restore: [],
};

shared.name = `Tpl Desk ${shared.sku}`;

const editUrl = () => `/admin/catalog/products/edit/${shared.productId}`;

const fieldGroup = (page, code) => page
  .locator('[data-control-group]')
  .filter({ has: page.locator(`#${code}`) })
  .last();

const previewModal = (page) => page.locator('[data-unsaved-ignore]').filter({ hasText: 'Template preview' });

const tributeMenu = (page) => page.locator('.tribute-container:visible');

const editorMenu = (page) => page.locator('.tox-autocompleter');

function trackPageErrors(page) {
  const errors = [];

  page.on('pageerror', (error) => errors.push(error.message));
  page.on('dialog', (dialog) => dialog.accept().catch(() => {}));

  return errors;
}

async function withAdminPage(browser, storageState, callback) {
  const context = await browser.newContext({ storageState });

  await context.addInitScript(HIDE_OVERLAYS);

  const page = await context.newPage();

  try {
    return await callback(page);
  } finally {
    await page.close();
    await context.close();
  }
}

async function setTemplates(page, attributeId, enabled) {
  await gotoPage(page, `/admin/catalog/attributes/edit/${attributeId}`);

  const toggle = page.locator('input#enable_templates');

  await toggle.waitFor({ state: 'attached', timeout: 20000 });

  const wasEnabled = await toggle.isChecked();

  if (wasEnabled !== enabled) {
    await page.locator('label[for="enable_templates"]').click();
    await clickSaveAndExpect(page, 'Save Attribute', /Attribute Updated Successfully/i);
  }

  return wasEnabled;
}

async function openProduct(page) {
  await gotoPage(page, editUrl());
  await page.waitForFunction((id) => window.tinymce?.get(id)?.initialized === true, RICH, { timeout: 30000 });
}

async function setRich(page, html) {
  await page.evaluate(({ id, content }) => {
    const editor = window.tinymce.get(id);

    editor.setContent(content);
    editor.fire('change');
    editor.save();
  }, { id: RICH, content: html });
}

async function richContent(page) {
  const content = await page.evaluate((id) => window.tinymce.get(id).getContent(), RICH);

  return content.replace(/&nbsp;| /g, ' ');
}

async function typeInRich(page, text) {
  await setRich(page, '<p></p>');

  await page.frameLocator(`#${RICH}_ifr`).locator('body').click();

  await page.evaluate((id) => {
    const editor = window.tinymce.get(id);

    editor.focus();
    editor.selection.select(editor.getBody(), true);
    editor.selection.collapse(false);
  }, RICH);

  await page.keyboard.type(text, { delay: 40 });
}

async function typeInPlain(page, code, text) {
  const field = page.locator(`#${code}`);

  await field.scrollIntoViewIfNeeded();
  await field.fill('');
  await field.click();
  await field.pressSequentially(text, { delay: 40 });

  return field;
}

async function openPreview(page, code) {
  const button = fieldGroup(page, code).getByRole('button', { name: 'Preview template' });

  await button.scrollIntoViewIfNeeded();
  await button.click();

  const modal = previewModal(page);

  await expect(modal).toBeVisible();

  return modal;
}

async function closePreview(page) {
  await previewModal(page).locator('.icon-cancel').click();
  await expect(previewModal(page)).toBeHidden();
}

test.describe.serial('Product attribute templates', { tag: '@global-state' }, () => {
  test.beforeAll(async ({ browser, workerStorageState }) => {
    test.setTimeout(240000);

    await withAdminPage(browser, workerStorageState, async (page) => {
      shared.productId = await createEditableSimpleProduct(page, shared.sku, shared.name);

      await gotoPage(page, editUrl());

      const attributeIds = [];

      for (const code of [PLAIN, RICH]) {
        attributeIds.push(await fieldGroup(page, code).getAttribute('data-attribute-id'));
      }

      for (const attributeId of attributeIds) {
        const wasEnabled = await setTemplates(page, attributeId, true);

        if (! wasEnabled) {
          shared.restore.push(attributeId);
        }
      }
    });
  });

  test.afterAll(async ({ browser, workerStorageState }) => {
    test.setTimeout(240000);

    await withAdminPage(browser, workerStorageState, async (page) => {
      for (const attributeId of shared.restore) {
        await setTemplates(page, attributeId, false).catch(() => {});
      }

      await deleteProductBySku(page, shared.sku).catch(() => {});
    });
  });

  test('shows the template help and preview only on fields that hold templates', async ({ adminPage }) => {
    const errors = trackPageErrors(adminPage);

    await openProduct(adminPage);

    for (const code of [PLAIN, RICH]) {
      await expect(fieldGroup(adminPage, code).getByRole('button', { name: 'Template help' })).toBeVisible();
      await expect(fieldGroup(adminPage, code).getByRole('button', { name: 'Preview template' })).toBeVisible();
    }

    await expect(fieldGroup(adminPage, WITHOUT_TEMPLATES).getByRole('button', { name: 'Template help' })).toHaveCount(0);
    await expect(fieldGroup(adminPage, WITHOUT_TEMPLATES).getByRole('button', { name: 'Preview template' })).toHaveCount(0);

    await fieldGroup(adminPage, PLAIN).getByRole('button', { name: 'Template help' }).click();

    await expect(adminPage.locator('p:visible').filter({ hasText: /^\s*Dynamic templates\s*$/ })).toHaveCount(1);
    await expect(adminPage.locator('code:visible').filter({ hasText: 'price.EUR' })).toHaveCount(1);

    await typeInPlain(adminPage, WITHOUT_TEMPLATES, 'Contact @');
    await adminPage.waitForTimeout(800);

    await expect(tributeMenu(adminPage)).toHaveCount(0);

    expect(errors).toEqual([]);
  });

  test('autocompletes attributes, currencies and filters in a plain text field', async ({ adminPage }) => {
    const errors = trackPageErrors(adminPage);

    await openProduct(adminPage);

    const field = await typeInPlain(adminPage, PLAIN, 'Buy @');

    await expect(tributeMenu(adminPage).locator('li').first()).toBeVisible();
    await expect(tributeMenu(adminPage).locator('li span.font-mono', { hasText: /^sku$/ })).toHaveCount(1);
    await expect(tributeMenu(adminPage).locator('li span.font-mono', { hasText: new RegExp(`^${PLAIN}$`) })).toHaveCount(0);

    await field.pressSequentially('nam', { delay: 40 });

    await expect(tributeMenu(adminPage).locator('li span.font-mono', { hasText: /^sku$/ })).toHaveCount(0);

    const highlighted = tributeMenu(adminPage).locator('li.highlighted-tribute-item span.font-mono');
    const code = (await highlighted.textContent()).trim();

    await adminPage.keyboard.press('Enter');

    await expect(field).toHaveValue(new RegExp(`^Buy \\{\\{ ${code} \\}\\}\\s?$`));

    await field.pressSequentially(' and @', { delay: 40 });
    await tributeMenu(adminPage).locator('li', { has: adminPage.locator('span.font-mono', { hasText: /^sku$/ }) }).click();

    await expect(field).toHaveValue(/and \{\{ sku \}\}\s?$/);

    await typeInPlain(adminPage, PLAIN, 'word@');
    await adminPage.waitForTimeout(800);

    await expect(tributeMenu(adminPage)).toHaveCount(0);

    await typeInPlain(adminPage, PLAIN, '{{ price.');

    await expect(tributeMenu(adminPage).locator('li').first()).toBeVisible();

    await adminPage.keyboard.press('Tab');

    await expect(field).toHaveValue(/^\{\{ price\.[A-Z]{3}/);

    await typeInPlain(adminPage, PLAIN, '{{ name.');
    await adminPage.waitForTimeout(800);

    await expect(tributeMenu(adminPage)).toHaveCount(0);

    await typeInPlain(adminPage, PLAIN, '{{ name |');

    await expect(tributeMenu(adminPage).locator('li', { hasText: 'upper' })).toBeVisible();
    await expect(tributeMenu(adminPage).locator('li', { hasText: 'format_date' })).toHaveCount(0);

    await field.pressSequentially('upp', { delay: 40 });
    await adminPage.keyboard.press('Enter');

    await expect(field).toHaveValue(/^\{\{ name \| upper/);

    await typeInPlain(adminPage, PLAIN, 'a | b');
    await adminPage.waitForTimeout(800);

    await expect(tributeMenu(adminPage)).toHaveCount(0);

    expect(errors).toEqual([]);
  });

  test('previews unsaved values, errors and warnings of a plain text field', async ({ adminPage }) => {
    const errors = trackPageErrors(adminPage);

    await openProduct(adminPage);

    await adminPage.locator(`#${PLAIN}`).fill('Buy {{ name | upper }}');

    let modal = await openPreview(adminPage, PLAIN);

    await expect(modal.locator('p.whitespace-pre-wrap')).toHaveText(`Buy ${shared.name.toUpperCase()}`);
    await expect(modal.getByText(/Uses the unsaved values/)).toBeVisible();

    await closePreview(adminPage);

    await adminPage.locator('#name').fill('Renamed desk');

    modal = await openPreview(adminPage, PLAIN);

    await expect(modal.locator('p.whitespace-pre-wrap')).toHaveText('Buy RENAMED DESK');

    await closePreview(adminPage);

    await adminPage.locator(`#${PLAIN}`).fill('{{ name | uper }} {{ cost }}');

    modal = await openPreview(adminPage, PLAIN);

    await expect(modal.getByText(/The filter "uper" is not supported/)).toBeVisible();

    await adminPage.locator(`#${PLAIN}`).fill('{{ cost }}');
    await modal.getByRole('button', { name: 'Refresh' }).click();

    await expect(modal.getByText(/The attribute "cost" has no value/)).toBeVisible();
    await expect(modal.getByText(/The filter "uper" is not supported/)).toHaveCount(0);

    expect(errors).toEqual([]);
  });

  test('keeps the template source on save and rejects invalid templates', async ({ adminPage }) => {
    const errors = trackPageErrors(adminPage);

    await openProduct(adminPage);

    await adminPage.locator(`#${PLAIN}`).fill('Buy {{ name | upper }}');
    await clickSave(adminPage, 'Save Product');

    await expect(adminPage.locator('#app').getByText(/Product updated successfully/i)).toBeVisible({ timeout: 20000 });

    await openProduct(adminPage);

    await expect(adminPage.locator(`#${PLAIN}`)).toHaveValue('Buy {{ name | upper }}');

    const modal = await openPreview(adminPage, PLAIN);

    await expect(modal.locator('p.whitespace-pre-wrap')).toHaveText(`Buy ${shared.name.toUpperCase()}`);

    await closePreview(adminPage);

    await adminPage.locator(`#${PLAIN}`).fill('{{ name | uper }}');
    await clickSave(adminPage, 'Save Product');

    await expect(adminPage.locator('#app').getByText(/The filter "uper" is not supported/).first()).toBeVisible({ timeout: 20000 });

    await openProduct(adminPage);

    await expect(adminPage.locator(`#${PLAIN}`)).toHaveValue('Buy {{ name | upper }}');

    expect(errors).toEqual([]);
  });

  test('autocompletes attributes, currencies and filters in a rich text field', async ({ adminPage }) => {
    const errors = trackPageErrors(adminPage);

    await openProduct(adminPage);

    await typeInRich(adminPage, 'Shop @');

    const menu = editorMenu(adminPage);

    await expect(menu.locator('.tox-collection__item').first()).toBeVisible();
    await expect(menu.locator('.product-template-assist-detail', { hasText: /^sku$/ })).toHaveCount(1);
    await expect(menu.locator('.product-template-assist-detail', { hasText: new RegExp(`^${RICH}$`) })).toHaveCount(0);

    const box = await menu.locator('.tox-menu').first().boundingBox();

    expect(box.width).toBeGreaterThanOrEqual(250);

    await adminPage.keyboard.type('nam', { delay: 40 });

    await expect(menu.locator('.product-template-assist-detail', { hasText: /^sku$/ })).toHaveCount(0);

    const active = menu.locator('.tox-collection__item--active .product-template-assist-detail');

    await expect(active).toHaveCount(1);

    const code = (await active.textContent()).trim();

    await adminPage.keyboard.press('Tab');

    await expect(menu).toBeHidden();
    expect(await richContent(adminPage)).toContain(`Shop {{ ${code} }}`);

    await adminPage.keyboard.type(' and @', { delay: 40 });
    await menu.locator('.tox-collection__item', { has: adminPage.locator('.product-template-assist-detail', { hasText: /^sku$/ }) }).click();

    await expect(menu).toBeHidden();
    expect(await richContent(adminPage)).toContain('and {{ sku }}');

    await typeInRich(adminPage, '{{ price.');

    await expect(menu.locator('.tox-collection__item-label').first()).toHaveText(/^[A-Z]{3}$/);

    await adminPage.keyboard.press('Escape');

    await typeInRich(adminPage, '{{ name |');

    await expect(menu.locator('.tox-collection__item-label', { hasText: /^upper$/ })).toHaveCount(1);
    await expect(menu.locator('.tox-collection__item-label', { hasText: /^format_date$/ })).toHaveCount(0);

    await adminPage.keyboard.type('upp', { delay: 40 });

    await expect(menu.locator('.tox-collection__item')).toHaveCount(1);

    await adminPage.keyboard.press('Enter');

    await expect(menu).toBeHidden();
    expect(await richContent(adminPage)).toContain('{{ name | upper');

    expect(errors).toEqual([]);
  });

  test('previews rich text output as formatted html and escapes inserted values', async ({ adminPage }) => {
    const errors = trackPageErrors(adminPage);

    await openProduct(adminPage);

    await setRich(adminPage, '<p><strong>{{ name | upper }}</strong> desk</p>');

    let modal = await openPreview(adminPage, RICH);

    await expect(modal.locator('.rich-content-preview strong')).toHaveText(shared.name.toUpperCase());

    await closePreview(adminPage);

    await adminPage.locator('#name').fill('<img src=x onerror="window.__templateXss=1">');
    await setRich(adminPage, '<p>{{ name }}</p>');

    modal = await openPreview(adminPage, RICH);

    await expect(modal.locator('.rich-content-preview')).toContainText('<img src=x');
    await expect(modal.locator('.rich-content-preview img')).toHaveCount(0);

    expect(await adminPage.evaluate(() => window.__templateXss)).toBeUndefined();
    expect(errors).toEqual([]);
  });
});
