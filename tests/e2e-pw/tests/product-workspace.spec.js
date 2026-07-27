const { test, expect } = require('../utils/fixtures');
const { navigateTo } = require('../utils/helpers');

/**
 * Product edit "workspace" overlay — covers the flow described in
 * packages/Webkul/Admin/src/Resources/views/catalog/products/edit/workspace.blade.php
 * and components/product/{section-card,workspace-panel}.blade.php:
 *
 *  - the right-rail Categories/Associations cards (`x-admin::product.section-card`,
 *    `@click="$productWorkspace.open(id)"`) open a workspace constrained to
 *    `#main-content` (`<v-product-workspace>`).
 *  - each section renders as `.product-workspace-panel[data-section-id="..."]`,
 *    toggled via `v-show` — only the active one is visible at a time.
 *  - the overlay header's segmented tab switcher (one button per registered
 *    section, labelled with the section's title) switches the active section
 *    without closing the overlay.
 *  - `Escape` closes the overlay (`store.close()`), hiding whichever panel was
 *    active.
 *
 * Uses the first product in the grid rather than a hardcoded id — the grid's
 * row-click handler navigates via `window.unopim.visit()` / `window.location.href`
 * (`components/datagrid/table.blade.php`), not a plain `<a href>`, so the test
 * waits on the resulting URL instead of reading an href attribute.
 */
test.describe('Product edit workspace overlay', () => {
	test('opens a section via its card, switches tabs without closing, and closes on Escape', async ({ adminPage }) => {
		await navigateTo(adminPage, 'products');

		await adminPage.locator('span[title="Edit"]').first().click();
		await adminPage.waitForURL(/\/admin\/catalog\/products\/edit\/\d+/, { timeout: 30000 });
		await adminPage.waitForLoadState('networkidle').catch(() => {});

		const categoriesCard = adminPage.locator('[role="button"]').filter({ hasText: 'Categories' }).first();
		const associationsCard = adminPage.locator('[role="button"]').filter({ hasText: 'Associations' }).first();
		const mainContent = adminPage.locator('#main-content');
		const workspaceFrame = adminPage.locator('.product-workspace-frame');
		const categoriesPanel = adminPage.locator('.product-workspace-panel[data-section-id="categories"]');
		const associationsPanel = adminPage.locator('.product-workspace-panel[data-section-id="associations"]');

		await expect(categoriesCard).toBeVisible();
		await expect(categoriesPanel).toBeHidden();

		await categoriesCard.click();
		await expect(categoriesPanel).toBeVisible();

		const [mainBox, frameBox, panelBox] = await Promise.all([
			mainContent.boundingBox(),
			workspaceFrame.boundingBox(),
			categoriesPanel.boundingBox(),
		]);

		expect(frameBox).not.toBeNull();
		expect(mainBox).not.toBeNull();
		expect(panelBox).not.toBeNull();
		expect(frameBox.x).toBeCloseTo(mainBox.x, 0);
		expect(frameBox.y).toBeCloseTo(mainBox.y, 0);
		expect(frameBox.x + frameBox.width).toBeLessThanOrEqual(mainBox.x + mainBox.width);
		expect(mainBox.x + mainBox.width - frameBox.x - frameBox.width).toBeLessThanOrEqual(16);
		expect(frameBox.height).toBeCloseTo(mainBox.height, 0);
		expect(panelBox.y).toBeCloseTo(mainBox.y + 64, 0);

		await adminPage.getByRole('button', { name: 'Associations', exact: true }).click();
		await expect(associationsPanel).toBeVisible();
		await expect(categoriesPanel).toBeHidden();

		await adminPage.keyboard.press('Escape');
		await expect(associationsPanel).toBeHidden();

		await associationsCard.click();
		await expect(associationsPanel).toBeVisible();
	});
});
