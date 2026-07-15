const { test, expect } = require('@playwright/test');

// This suite targets the working-dir app directly and logs in itself, so it
// does not depend on the shared saved auth state (which is for another origin).
test.use({ storageState: { cookies: [], origins: [] } });

test.describe('admin ajax navigation', () => {
    test.beforeEach(async ({ page }) => {
        const email = process.env.ADMIN_EMAIL || 'admin@example.com';
        const password = process.env.ADMIN_PASSWORD || 'admin123';

        await page.goto('/admin/login');
        await page.getByRole('textbox', { name: 'Email Address' }).fill(email);
        await page.getByRole('textbox', { name: 'Password' }).fill(password);
        await page.getByRole('button', { name: 'Sign In' }).click();
        await page.waitForURL(/\/admin\/dashboard/, { timeout: 20000 });

        // Only assert against an app that actually ships ajax navigation.
        const enabled = await page.evaluate(() => !! window.__ajaxNavInitialised);
        test.skip(! enabled, 'Ajax navigation is not deployed on this base URL');
    });

    test('navigates between pages without a full reload and supports back', async ({ page }) => {
        await page.waitForLoadState('networkidle');

        // Guard against script re-execution regressions (redeclarations,
        // templates executed as JS, libraries not ready) during navigation.
        const consoleErrors = [];
        page.on('console', (message) => {
            if (message.type() === 'error') {
                consoleErrors.push(message.text());
            }
        });
        page.on('pageerror', (error) => consoleErrors.push(error.message));

        await page.evaluate(() => {
            window.__navSentinel = 'KEEP';
            window.__navEvents = [];
            document.addEventListener('unopim:navigate:before', (e) => window.__navEvents.push('before:' + e.detail.url));
            document.addEventListener('unopim:navigate:success', (e) => window.__navEvents.push('success:' + e.detail.url));
        });

        await page.evaluate(() => {
            const link = [...document.querySelectorAll('a[href]')]
                .find((a) => a.href.endsWith('/admin/catalog/products'));
            link.click();
        });

        await page.waitForURL(/\/admin\/catalog\/products/);
        await expect(page.locator('#app')).toContainText(/product/i);

        // Sentinel intact => no full reload happened.
        expect(await page.evaluate(() => window.__navSentinel)).toBe('KEEP');

        await page.evaluate(() => {
            const link = [...document.querySelectorAll('a[href]')]
                .find((a) => a.href.endsWith('/admin/catalog/categories'));
            link.click();
        });
        await page.waitForURL(/\/admin\/catalog\/categories/);
        expect(await page.evaluate(() => window.__navSentinel)).toBe('KEEP');

        await page.goBack();
        await page.waitForURL(/\/admin\/catalog\/products/);
        expect(await page.evaluate(() => window.__navSentinel)).toBe('KEEP');

        // Lifecycle events fired for each visit (listeners survive re-mounts
        // because they are on `document`, not the Vue app).
        const events = await page.evaluate(() => window.__navEvents);
        expect(events.filter((e) => e.startsWith('before:')).length).toBeGreaterThanOrEqual(2);
        expect(events.filter((e) => e.startsWith('success:')).length).toBeGreaterThanOrEqual(2);
        expect(events.some((e) => e.includes('/catalog/categories'))).toBeTruthy();

        // No script re-execution errors leaked to the console during navigation.
        expect(consoleErrors).toEqual([]);
    });

    test('datagrid edit action navigates without a full reload', async ({ page }) => {
        await page.goto('/admin/catalog/categories');
        await page.waitForLoadState('networkidle');

        // Wait for the datagrid rows and their edit action to render.
        const editAction = page.locator('#app [class*="icon-edit"]').first();
        await expect(editAction).toBeVisible({ timeout: 15000 });

        await page.evaluate(() => { window.__navSentinel = 'KEEP'; });

        await editAction.click();

        await page.waitForURL(/\/admin\/catalog\/categories\/edit\//);

        // Sentinel intact => the datagrid GET action went through ajax nav,
        // not window.location (which would have reloaded and wiped it).
        expect(await page.evaluate(() => window.__navSentinel)).toBe('KEEP');
    });

    // Regression: a GET link that server-side 302-redirects (ai-agent/settings
    // → magic-ai/settings) must update the address bar to the FINAL
    // url. If it stays on the source url, a form with `action=""` on the landed
    // page posts back to the redirect-only route → 405 Method Not Allowed.
    test('ajax nav syncs the address bar to the final redirected url', async ({ page }) => {
        await page.waitForLoadState('networkidle');

        const hasRedirectLink = await page.evaluate(() =>
            !! [...document.querySelectorAll('a[href]')].find((a) => a.href.endsWith('/admin/ai-agent/settings')));
        test.skip(! hasRedirectLink, 'ai-agent/settings redirect link not present on this build');

        await page.evaluate(() => {
            const link = [...document.querySelectorAll('a[href]')]
                .find((a) => a.href.endsWith('/admin/ai-agent/settings'));
            link.click();
        });

        // Address bar must land on the redirect TARGET, not the source route.
        await page.waitForURL(/\/admin\/magic-ai\/settings/, { timeout: 20000 });
        expect(new URL(page.url()).pathname).not.toContain('/ai-agent/settings');

        // Saving the landed configuration form must not 405 (posts to the
        // synced url = configuration store, not the GET-only settings route).
        const saveStatus = await page.evaluate(async () => {
            const form = document.querySelector('#app form');
            if (! form) { return null; }
            const action = form.getAttribute('action');
            const target = ! action ? window.location.href : action;
            const res = await fetch(target, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin',
                body: new FormData(form),
            });
            return res.status;
        });
        expect(saveStatus).not.toBe(405);
    });
});
