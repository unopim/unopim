const { test, expect } = require('../../utils/fixtures');
const { clickSave, generateUid, gotoPage } = require('../../utils/helpers');
const { createEditableSimpleProduct, deleteProductBySku } = require('../../utils/product-helpers');

const PAYLOAD = '<iframe src="https://www.youtube.com/embed/abc" width="560" sandbox="allow-top-navigation" srcdoc="<script>parent.__xss=1</script>" onload="parent.__xss=2"></iframe>'
  + '<iframe src="https://evil.example/embed/x"></iframe>'
  + '<iframe src="javascript:parent.__xss=3"></iframe>'
  + '<a href="https://example.com" target="_blank" rel="opener">new window</a>'
  + '<a href="https://example.com" target="_top">top</a>'
  + '<img src="https://example.com/a.png" onerror="parent.__xss=4"><script>parent.__xss=5</script>';

/** Wait for a rich-text editor to load and return its id and content; pass an id to wait for its saved content. */
async function richTextEditor(page, editorId = null) {
  await page.waitForFunction((id) => {
    const editor = window.tinymce && (id ? tinymce.get(id) : tinymce.get()[0]);

    return !! editor && editor.initialized && (! id || editor.getContent() !== '');
  }, editorId, { timeout: 20000 });

  return page.evaluate((id) => {
    const editor = id ? tinymce.get(id) : tinymce.get()[0];

    return { id: editor.id, content: editor.getContent() };
  }, editorId);
}

/** Assert the embed and link rules the editor and the server both enforce. */
function expectSafeRichText(content) {
  expect(content).toContain('src="https://www.youtube.com/embed/abc"');
  expect(content).toMatch(/sandbox="allow-scripts allow-same-origin allow-presentation allow-popups"/);
  expect(content).toMatch(/target="_blank" rel="(noopener noreferrer|noreferrer noopener)"/);
  expect(content).not.toContain('evil.example');
  expect(content).not.toContain('javascript:');
  expect(content).not.toContain('srcdoc');
  expect(content).not.toContain('allow-top-navigation');
  expect(content).not.toContain('onerror');
  expect(content).not.toContain('<script');
  expect(content).not.toContain('_top');
}

test.describe('Rich text video embeds and new window links', () => {
  test('keeps an allowed video embed and a new window link through a product save', async ({ adminPage }) => {
    const sku = `embed-${generateUid()}`;

    try {
      const id = await createEditableSimpleProduct(adminPage, sku, `Embed ${sku}`);

      await gotoPage(adminPage, `/admin/catalog/products/edit/${id}`);

      const { id: editorId } = await richTextEditor(adminPage);

      const inEditor = await adminPage.evaluate(({ editorId, payload }) => {
        window.__xss = 0;

        const editor = tinymce.get(editorId);
        editor.setContent(payload);
        editor.fire('change');
        editor.save();

        return editor.getContent();
      }, { editorId, payload: PAYLOAD });

      expectSafeRichText(inEditor);

      await clickSave(adminPage, 'Save Product');
      await expect(adminPage.locator('#app').getByText(/Product updated successfully/i)).toBeVisible({ timeout: 20000 });

      await gotoPage(adminPage, `/admin/catalog/products/edit/${id}`);

      const { content: afterSave } = await richTextEditor(adminPage, editorId);

      expectSafeRichText(afterSave);
      expect(await adminPage.evaluate(() => window.__xss ?? 0)).toBe(0);
    } finally {
      await deleteProductBySku(adminPage, sku);
    }
  });
});
