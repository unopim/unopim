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
      let json = null;
      try {
        json = await res.json();
      } catch {
        json = null;
      }
      return { status: res.status, json };
    }, { name, type, content });
  }

  for (const c of CASES) {
    test(`rejects ${c.label}`, async ({ adminPage }) => {
      await adminPage.goto('/admin/account', { waitUntil: 'domcontentloaded' });

      const { status, json } = await upload(adminPage, c);

      // A 422 with an image-field error proves AccountForm's upload rules ran
      // and rejected the file — not that an auth/CSRF failure blocked it.
      expect(status, `${c.name} must fail validation with 422`).toBe(422);

      const imageError = Object.keys(json?.errors ?? {}).some((key) => key.startsWith('image'));
      expect(imageError, `${c.name} must be rejected by an image-field rule`).toBe(true);
    });
  }
});
