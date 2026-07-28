import { test, expect } from '@playwright/test';

const LARGE_FAMILY_PRODUCT = 499;

// Carries required attributes in groups past the first one.
const REQUIRED_GAP_PRODUCT = 487;

const scrollToBottom = (page) => page.evaluate(() => {
    const el = document.querySelector('#main-content');

    if (el) {
        el.scrollTop = el.scrollHeight;
    }
});

const panels = (page) => page.locator('[data-attribute-group]');

test('loads further attribute groups as the page is scrolled', async ({ page }) => {
    const failures = [];

    page.on('pageerror', (error) => failures.push(String(error)));

    await page.goto(`/admin/catalog/products/edit/${LARGE_FAMILY_PRODUCT}`);

    await expect(panels(page)).toHaveCount(1);

    for (let i = 0; i < 3; i++) {
        await scrollToBottom(page);
        await page.waitForTimeout(1200);
    }

    expect(await panels(page).count()).toBeGreaterThan(1);

    await expect(
        panels(page).nth(1).locator('input[name], select[name], textarea[name]').first()
    ).toBeAttached();

    expect(failures).toEqual([]);
});

test('an appended group collapses and expands', async ({ page }) => {
    await page.goto(`/admin/catalog/products/edit/${LARGE_FAMILY_PRODUCT}`);

    await scrollToBottom(page);
    await page.waitForTimeout(1500);

    const appended = panels(page).nth(1);
    const field = appended.locator('input[type="text"][name]').first();
    const chevron = appended.locator('[class*="icon-chevron"]').first();

    await expect(field).toBeVisible();

    await chevron.click();
    await expect(field).toBeHidden();

    await chevron.click();
    await expect(field).toBeVisible();
});

test('a collapsed group stays collapsed after a reload', async ({ page }) => {
    await page.goto(`/admin/catalog/products/edit/${LARGE_FAMILY_PRODUCT}`);

    const field = panels(page).first().locator('input[type="text"][name]').first();

    await expect(field).toBeVisible();

    await panels(page).first().locator('[class*="icon-chevron"]').first().click();
    await expect(field).toBeHidden();

    await page.reload();

    await expect(panels(page).first().locator('input[type="text"][name]').first()).toBeHidden();

    await panels(page).first().locator('[class*="icon-chevron"]').first().click();

    await page.reload();

    await expect(panels(page).first().locator('input[type="text"][name]').first()).toBeVisible();
});

test('editing an appended group opens the save bar', async ({ page }) => {
    await page.goto(`/admin/catalog/products/edit/${LARGE_FAMILY_PRODUCT}`);

    await scrollToBottom(page);
    await page.waitForTimeout(1500);

    await panels(page).nth(1).locator('input[type="text"][name]').first().fill('scroll append check');

    await expect(page.locator('.unsaved-bar')).toBeVisible();
});

test('a failed save reopens the collapsed group holding the invalid field', async ({ page }) => {
    await page.goto(`/admin/catalog/products/edit/${LARGE_FAMILY_PRODUCT}`);

    const skuName = await page.evaluate(() => {
        const input = [...document.querySelectorAll('[data-attribute-group] input[name]')]
            .find((element) => element.name.includes('[sku]'));

        return input ? input.name : null;
    });

    test.skip(! skuName, 'the first group carries no sku field');

    const field = page.locator(`[name="${skuName}"]`).first();

    await field.fill('');

    await panels(page)
        .filter({ has: page.locator(`[name="${skuName}"]`) })
        .first()
        .locator('[class*="icon-chevron"]')
        .first()
        .click();

    await expect(field).toBeHidden();

    // The dev debugbar overlays the bar, so the click is dispatched directly.
    await page.locator('[data-unsaved-save]').evaluate((element) => element.click());

    await expect(field).toBeVisible();
});

test('keeps loading groups while the loaded ones are collapsed', async ({ page }) => {
    await page.goto(`/admin/catalog/products/edit/${LARGE_FAMILY_PRODUCT}`);

    for (let i = 0; i < 3; i++) {
        await scrollToBottom(page);
        await page.waitForTimeout(1000);
    }

    const chevrons = page.locator('[data-attribute-group] [class*="icon-chevron"]');

    const toCollapse = Math.min(4, await chevrons.count());

    for (let i = 0; i < toCollapse; i++) {
        await chevrons.nth(i).click();
        await page.waitForTimeout(200);
    }

    await page.reload();

    // Collapsed panels barely grow the page, so the sentinel can stay on screen;
    // the loader has to keep pulling groups instead of stalling after the first.
    await expect.poll(() => panels(page).count(), { timeout: 20000 }).toBeGreaterThan(2);
});

test('the whole group header toggles, and the chevron explains itself', async ({ page }) => {
    await page.goto(`/admin/catalog/products/edit/${LARGE_FAMILY_PRODUCT}`);

    const panel = panels(page).first();
    const field = panel.locator('input[type="text"][name]').first();
    const header = panel.locator('[role="button"]').first();
    const chevron = panel.locator('[class*="icon-chevron"]').first();

    await expect(field).toBeVisible();
    await expect(chevron).toHaveAttribute('title', 'Collapse');
    await expect(header).toHaveAttribute('aria-expanded', 'true');

    await header.click();

    await expect(field).toBeHidden();
    await expect(chevron).toHaveAttribute('title', 'Expand');
    await expect(header).toHaveAttribute('aria-expanded', 'false');

    await header.click();

    await expect(field).toBeVisible();
});

test('a required field in an unloaded group is fetched, shown and explained', async ({ page }) => {
    let firstError = null;

    page.on('response', async (response) => {
        if (response.request().method() === 'POST' && response.url().includes(`/products/edit/${REQUIRED_GAP_PRODUCT}`)) {
            try {
                firstError = Object.keys(JSON.parse(await response.text()).errors || {})[0];
            } catch {
                // not a validation payload
            }
        }
    });

    await page.goto(`/admin/catalog/products/edit/${REQUIRED_GAP_PRODUCT}`);

    await expect(panels(page)).toHaveCount(1);

    // Dirty the form without touching any group beyond the first.
    await page.locator('input[name="values[common][url_key]"]').fill(`url_key_${Date.now()}`);

    await page.locator('[data-unsaved-save]').evaluate((element) => element.click());

    await page.waitForTimeout(4000);

    test.skip(! firstError, 'this product has no required attribute left empty');

    await expect.poll(() => panels(page).count(), { timeout: 15000 }).toBeGreaterThan(1);

    const notice = page.locator('[data-server-error]').first();

    await expect(notice).toBeVisible();
    await expect(notice).toContainText('required');
});
