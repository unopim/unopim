const { test, expect } = require('@playwright/test');

test.use({ storageState: { cookies: [], origins: [] } });

test.beforeEach(async ({ page }) => {
  await page.goto('/admin/login');
  await expect(page.getByRole('heading', { name: 'Sign In' })).toBeVisible();
});

for (const { name, extensions, expected } of [
  { name: 'canonical', extensions: ['pdf', 'docx'], expected: ['pdf', 'docx'] },
  { name: 'dotted', extensions: ['.pdf', '.docx'], expected: ['pdf', 'docx'] },
  { name: 'uppercase', extensions: ['PDF', 'DOCX'], expected: ['pdf', 'docx'] },
  { name: 'dotted uppercase', extensions: ['.PDF', '.DOCX'], expected: ['pdf', 'docx'] },
  { name: 'default', extensions: [], expected: [] },
]) {
  test(`sends normalized extensions for ${name} media options`, async ({ page }) => {
    await page.route('**/admin/media/scan', (route) => route.fulfill({ json: { valid: true } }));
    const requestPromise = page.waitForRequest('**/admin/media/scan');

    await page.evaluate(async (acceptedExtensions) => {
      const properties = window.app.config.globalProperties;

      await properties.$scanMedia.call(properties, new File(['%PDF-1.7\n%%EOF'], 'document.pdf', {
        type: 'application/pdf',
      }), {
        url: '/admin/media/scan',
        acceptedExtensions,
      });
    }, extensions);

    const request = await requestPromise;
    const form = await new Response(request.postDataBuffer(), {
      headers: { 'Content-Type': request.headers()['content-type'] },
    }).formData();

    expect(form.getAll('accepted_extensions[]')).toEqual(expected);
    expect(form.get('file').name).toBe('document.pdf');
    expect(form.get('is_image')).toBe('0');
  });
}
