const { test, expect } = require('@playwright/test');
const { login } = require('../../utils/login');

const NAV_EVENTS = ['unopim:navigate:before', 'unopim:navigate:success', 'unopim:navigate:error'];

// Repro attempt: user reports that after creating an attribute via the quick-create
// modal, opening it from the grid shows STALE content until a hard reload.
// Journey: grid -> create A -> edit A -> back to grid (SPA) -> create B -> edit B
// then: grid -> row edit action on B. All navigation stays inside the SPA layer.
test('SPA journey: consecutive creates land on fresh edit pages', async ({ page }) => {
  const stamp = Date.now().toString().slice(-7);
  const codeA = 'dbga' + stamp;
  const codeB = 'dbgb' + stamp;

  await page.goto('/admin/catalog/attributes');

  if (await page.getByRole('textbox', { name: 'Email Address' }).isVisible().catch(() => false)) {
    await login(page);
    await page.goto('/admin/catalog/attributes');
  }

  page.on('console', (msg) => {
    if (/NAVEVT|error|Error/i.test(msg.text())) console.log('PAGE CONSOLE:', msg.type(), msg.text().slice(0, 300));
  });

  await page.addInitScript((events) => {
    events.forEach((name) => {
      document.addEventListener(name, (e) => {
        console.log('NAVEVT', name, JSON.stringify({ url: e.detail?.url, error: String(e.detail?.error || '') }));
      });
    });
  }, NAV_EVENTS);

  // init script applies on next navigation — force one so listeners attach
  await page.goto('/admin/catalog/attributes');

  page.on('response', async (res) => {
    if (/attributes\/edit\/\d+/.test(res.url()) && (res.request().resourceType() === 'fetch' || res.request().resourceType() === 'document')) {
      let marker = '?';
      try {
        const body = await res.text();
        marker = body.includes('dbgb') ? 'CONTAINS-B' : body.includes('dbga') ? 'CONTAINS-A' : 'neither';
      } catch (e) { marker = 'unreadable'; }
      console.log('NAV RESPONSE:', res.status(), res.url(), marker);
    }
  });

  const createViaModal = async (code, name) => {
    await page.getByRole('button', { name: 'Create Attribute' }).click();
    await page.locator('input[name*="[name]"]').first().waitFor({ state: 'visible' });
    await page.locator('input[name*="[name]"]').first().fill(name);
    await page.locator('input[name="code"]').fill(code);
    await page.locator('.multiselect:has(input[name="type"])').first().click();
    await page.getByRole('option', { name: /^Text\b/ }).first().click();
    await page.getByRole('button', { name: /^Save/ }).last().click();
    await page.waitForURL(/attributes\/edit\/\d+/, { timeout: 15000 });
  };

  await createViaModal(codeA, 'Debug A');
  await expect(page.locator('input[name="code"]').first()).toHaveValue(codeA, { timeout: 10000 });

  // Back to grid via in-app link (SPA nav), not a fresh page load
  await page.getByRole('link', { name: 'Back' }).click();
  await page.waitForURL(/catalog\/attributes$/, { timeout: 15000 });

  // Trace #app swaps and which attribute code the DOM holds at each step
  await page.evaluate(() => {
    window.__trace = [];
    const codeNow = () => {
      const el = document.querySelector('#app input[name="code"]');
      return el ? el.getAttribute('value') || el.value : null;
    };
    const obs = new MutationObserver((muts) => {
      for (const m of muts) {
        for (const n of m.addedNodes) {
          if (n.nodeType === 1 && (n.id === 'app' || n.querySelector?.('#app'))) {
            window.__trace.push({ t: performance.now() | 0, ev: 'app-node-added', code: codeNow() });
          }
        }
        for (const n of m.removedNodes) {
          if (n.nodeType === 1 && n.id === 'app') {
            window.__trace.push({ t: performance.now() | 0, ev: 'app-node-removed' });
          }
        }
      }
    });
    obs.observe(document.body, { childList: true, subtree: false });

    // Also sample the code value every 200ms
    window.__sampler = setInterval(() => {
      window.__trace.push({ t: performance.now() | 0, ev: 'sample', code: codeNow(), url: location.pathname });
    }, 200);
  });

  await createViaModal(codeB, 'Debug B');
  await page.waitForTimeout(2000);

  const trace = await page.evaluate(() => { clearInterval(window.__sampler); return window.__trace; });
  console.log('TRACE:', JSON.stringify(trace));

  const rootDiag = await page.evaluate((codes) => {
    const c = window.app?._component || {};
    const t = typeof c.template === 'string' ? c.template : '';
    return {
      hasTemplate: !!c.template,
      templateLen: t.length,
      templateHasA: t.includes(codes.a),
      templateHasB: t.includes(codes.b),
      templateHasEditForm: t.includes('attribute-edit-form'),
      hasRender: typeof c.render === 'function',
    };
  }, { a: codeA, b: codeB });
  console.log('ROOT COMPONENT:', JSON.stringify(rootDiag));

  const liveDiag = await page.evaluate(() => {
    const appEl = document.querySelector('#app');
    return {
      containerIsCurrent: window.app?._container === appEl,
      hasVueApp: !!appEl.__vue_app__,
      vueAppIsWindowApp: appEl.__vue_app__ === window.app,
      bodyAppCount: document.querySelectorAll('[id="app"]').length,
      // does the visible heading belong to server HTML of A or a Vue render?
      appFirstChars: appEl.innerHTML.slice(0, 120),
    };
  });
  console.log('LIVE DIAG:', JSON.stringify(liveDiag));
  console.log('URL AFTER CREATE B:', page.url());
  console.log('CODE SHOWN:', await page.locator('input[name="code"]').first().inputValue());

  const diag = await page.evaluate(async (codes) => {
    const raw = await fetch(location.href, { credentials: 'same-origin' }).then(r => r.text());
    const inputs = [...document.querySelectorAll('input[name="code"]')];
    return {
      rawHasA: raw.includes(codes.a),
      rawHasB: raw.includes(codes.b),
      appCount: document.querySelectorAll('#app').length,
      codeInputs: inputs.map(i => ({
        attr: i.getAttribute('value'),
        prop: i.value,
        inApp: !!i.closest('#app'),
        connected: i.isConnected,
      })),
    };
  }, { a: codeA, b: codeB });
  console.log('DIAG:', JSON.stringify(diag, null, 2));

  const context = await page.evaluate(() => {
    const inputs = [...document.querySelectorAll('input[name="code"]')];
    return {
      heading: document.querySelector('h1,h2,.text-xl')?.textContent?.trim(),
      labels: [...document.querySelectorAll('input[name*="[name]"], input[name$="[name]"]')].map(i => i.value),
      inputAncestors: inputs.map(i => {
        let chain = [];
        let el = i.parentElement;
        for (let d = 0; el && d < 6; d++) { chain.push(el.tagName + (el.id ? '#' + el.id : '') + (el.className && typeof el.className === 'string' ? '.' + el.className.split(' ').slice(0,2).join('.') : '')); el = el.parentElement; }
        return chain.join(' < ');
      }),
      templates: [...document.querySelectorAll('script[type="text/x-template"]')].map(t => ({ id: t.id, hasA: t.textContent.includes('dbga'), hasB: t.textContent.includes('dbgb') })),
    };
  });
  console.log('CONTEXT:', JSON.stringify(context, null, 2));
  await expect(page.locator('input[name="code"]').first()).toHaveValue(codeB, { timeout: 5000 });

  // Now grid -> search B -> row edit action
  await page.getByRole('link', { name: 'Back' }).click();
  await page.waitForURL(/catalog\/attributes$/, { timeout: 15000 });

  await page.getByPlaceholder('Search').first().fill(codeB);
  await page.keyboard.press('Enter');
  await page.waitForTimeout(1500);

  // Click the row's edit action (pencil icon link containing /edit/)
  const editLink = page.locator(`a[href*="/attributes/edit/"]`).first();
  await editLink.click();
  await page.waitForURL(/attributes\/edit\/\d+/, { timeout: 15000 });

  const shownCode = await page.locator('input[name="code"]').first().inputValue();
  console.log('ROW-EDIT SHOWS CODE:', shownCode, 'EXPECTED:', codeB);
  expect(shownCode).toBe(codeB);
});
