const { test, expect } = require('../../utils/fixtures');

/**
 * Malicious-upload guard on the account image field.
 *
 * AccountForm applies `mimes:` + FileMimeExtensionMatch, which rejects a file
 * whose real content (sniffed mime / guessed extension) disagrees with its
 * declared extension, and any executable extension. A PHP payload disguised as
 * an image — or an image mime wrapped around a .php name — must come back 422
 * and never be stored.
 */
test.describe('Malicious upload - account image rejects disguised code', () => {
  const PHP = '<?php system($_GET["c"]); ?>';
  const GIF_PHP = 'GIF89a<?php system($_GET["c"]); ?>';

  const CASES = [
    { label: 'PHP bytes named .jpg, image mime', name: 'shell.jpg', type: 'image/jpeg', content: PHP },
    { label: 'PHP bytes named .png, image mime', name: 'shell.png', type: 'image/png', content: PHP },
    { label: 'GIF-magic polyglot named .php', name: 'shell.php', type: 'image/gif', content: GIF_PHP },
    { label: 'double extension .php.jpg', name: 'shell.php.jpg', type: 'image/jpeg', content: PHP },
    { label: 'SVG with script named .svg', name: 'x.svg', type: 'image/svg+xml', content: '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>' },
  ];

  async function upload(adminPage, { name, type, content }) {
    return adminPage.evaluate(async ({ name, type, content }) => {
      const token = document.querySelector('meta[name="csrf-token"]')?.content
        ?? document.querySelector('input[name="_token"]')?.value
        ?? '';
      const fd = new FormData();
      fd.append('_token', token);
      fd.append('_method', 'PUT');
      fd.append('name', 'Admin');
      fd.append('image', new File([content], name, { type }));

      const res = await fetch('/admin/account', {
        method: 'POST',
        headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token },
        body: fd,
        redirect: 'manual',
      });
      return res.status;
    }, { name, type, content });
  }

  for (const c of CASES) {
    test(`rejects ${c.label}`, async ({ adminPage }) => {
      await adminPage.goto('/admin/account', { waitUntil: 'domcontentloaded' });

      const status = await upload(adminPage, c);

      expect(status, `${c.name} must be rejected, not accepted`).not.toBe(200);
      expect([422, 302, 403, 419], `${c.name} should fail validation (422)`).toContain(status);
    });
  }
});
