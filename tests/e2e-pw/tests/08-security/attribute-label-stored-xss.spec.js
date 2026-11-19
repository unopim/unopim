const { test, expect } = require('../../utils/fixtures');
const { navigateTo, searchInDataGrid, generateUid } = require('../../utils/helpers');
const { createGalleryAttribute, deleteAttributeByCode } = require('../../utils/gallery-helpers');

/**
 * Stored-XSS guard on a user-controlled attribute label.
 *
 * The label is persisted, then echoed back in the datagrid and the edit form.
 * If any sink renders it unescaped the injected <img onerror> executes and sets
 * window.__xssFired. The payload fires only when live in the DOM, so an
 * undefined flag after render proves the value was escaped. The attribute type
 * is irrelevant to the sink — the shared Gallery helper is reused to create it.
 */
test.describe('Stored XSS - attribute label is rendered escaped', () => {
  test.setTimeout(120000);

  const uid = generateUid();
  const code = `xss_probe_${uid}`;
  const marker = `XSS_${uid}`;
  const payload = `<img src=x onerror="window.__xssFired=true">${marker}`;

  async function assertNotFired(adminPage, where) {
    const fired = await adminPage.evaluate(() => window.__xssFired === true);
    expect(fired, `attribute label must not execute script in the ${where}`).toBe(false);
  }

  test('an onerror label never executes in grid or edit form', async ({ adminPage }) => {
    adminPage.on('dialog', async (dialog) => {
      await dialog.dismiss().catch(() => {});
      throw new Error(`unexpected ${dialog.type()} dialog — payload executed: ${dialog.message()}`);
    });

    await createGalleryAttribute(adminPage, code, payload);

    await adminPage.waitForLoadState('networkidle').catch(() => {});
    await assertNotFired(adminPage, 'edit form');

    await navigateTo(adminPage, 'attributes');
    await searchInDataGrid(adminPage, code);
    await adminPage.waitForLoadState('networkidle').catch(() => {});

    await assertNotFired(adminPage, 'datagrid');

    const rendered = await adminPage.locator('#app').textContent();
    expect(rendered, 'the literal payload text must be present (escaped), proving it was stored').toContain(marker);

    await deleteAttributeByCode(adminPage, code);
  });
});
