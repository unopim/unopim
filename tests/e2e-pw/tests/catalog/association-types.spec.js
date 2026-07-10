const { test, expect } = require('../../utils/fixtures');
const { searchInDataGrid } = require('../../utils/helpers');

/**
 * Association Types (Catalog > Association Types) — Plan 1 slice.
 *
 * Covers:
 *  (a) the 3 seeded defaults (Related Products / Up Sells / Cross Sells) show
 *      on the index grid and expose no delete control (they are
 *      `is_user_defined = 0`, so `AssociationTypeDataGrid::prepareActions()`
 *      hides the delete action entirely for their rows).
 *  (b) a custom type `bundle_kit` ("Bundle / Kit") can be created with a
 *      required `quantity` field (type Text, validation Number) via the
 *      reusable field-builder component.
 *  (c) reloading the edit page shows `quantity` still persisted server-side.
 *  (d) the custom type can be deleted (unlike the defaults).
 *
 * Routes: admin.catalog.association_types.* -> /admin/catalog/association-types[...]
 * Blades: packages/Webkul/Admin/src/Resources/views/catalog/associations/types/{index,create,edit}.blade.php
 *         packages/Webkul/Admin/src/Resources/views/components/associations/field-builder.blade.php
 */

const INDEX_URL = '/admin/catalog/association-types';

/**
 * Every `x-admin::form` (the create/edit association-type pages included)
 * defaults to `trackDirty + hideSaveWhenTracked`: as soon as the page mounts,
 * `v-unsaved-changes` PHYSICALLY REMOVES the form's own named submit button
 * (e.g. "Save Association Type") from the DOM — see
 * packages/Webkul/Admin/src/Resources/views/components/form/unsaved-changes.blade.php
 * (`removeInFormSave()`), so the ONLY way to submit is the floating
 * "You have unsaved changes" bar's generic "Save changes" button. This is
 * current, deliberate app behaviour (not specific to association types), so
 * the spec targets that button rather than the page's own labelled one.
 */
async function saveViaUnsavedChangesBar(page, toastPattern) {
	const currentUrl = page.url();
	const regex = toastPattern instanceof RegExp ? toastPattern : new RegExp(toastPattern, 'i');

	const navPromise = page.waitForURL((url) => url.toString() !== currentUrl, { timeout: 20000 });
	const toastPromise = page.locator('#app').getByText(regex).first().waitFor({ state: 'visible', timeout: 20000 });

	await page.getByRole('button', { name: 'Save changes' }).click();

	// Either the toast appears OR the page redirects (create redirects to the
	// index listing) — fails only if BOTH time out.
	await Promise.any([navPromise, toastPromise]);
}

/**
 * Opens the field-builder's "Add Field" modal, fills it in, and saves it.
 * Scoped to the modal's own nested `<form>` (identified as the `<form>`
 * containing the "Save Field" button) because the page's own "Code" field
 * and the modal's "Code" field share the same accessible name.
 */
async function addAssociationTypeField(page, { code, type, validation, required, section }) {
	// Two "Add Field" triggers exist while the list is empty (the panel
	// header button and the empty-state button); `.first()` is the header one.
	await page.getByText('Add Field', { exact: true }).first().click();

	const modal = page.locator('form').filter({ has: page.getByRole('button', { name: 'Save Field' }) }).last();

	await modal.getByRole('textbox', { name: 'Code' }).fill(code);

	await modal.locator('.multiselect__tags').filter({ hasText: 'Select option' }).first().click();
	await page.getByRole('option', { name: type }).first().click();

	if (validation) {
		await modal.locator('.multiselect__tags').filter({ hasText: 'Select option' }).first().click();
		await page.getByRole('option', { name: validation }).first().click();
	}

	if (required) {
		await modal.getByText('Is Required', { exact: true }).click();
	}

	// Display Section is required but its pre-filled default doesn't visually
	// take on first open, so it must always be selected explicitly.
	await modal.locator('.multiselect__tags').filter({ hasText: 'Select option' }).first().click();
	await page.getByRole('option', { name: section }).first().click();

	await modal.getByRole('button', { name: 'Save Field' }).click();

	await expect(page.getByText(code, { exact: true }).first()).toBeVisible();
}

/**
 * Best-effort cleanup: deletes a custom association type by code if a
 * previous, interrupted run left it behind. Silently no-ops otherwise.
 */
async function ensureAssociationTypeAbsent(page, code) {
	await page.goto(INDEX_URL, { waitUntil: 'load' });
	await searchInDataGrid(page, code);

	const deleteBtn = page.locator('span[title="Delete"]').first();

	if (await deleteBtn.isVisible({ timeout: 3000 }).catch(() => false)) {
		await deleteBtn.click();
		await page.getByRole('button', { name: 'Delete' }).click();
		await page.waitForLoadState('load');
		await page.waitForTimeout(500);
	}
}

test.describe('UnoPim Association Type Tests', () => {

	test('seeded default association types show and expose no delete control', async ({ adminPage }) => {
		for (const name of ['Related Products', 'Up Sells', 'Cross Sells']) {
			await adminPage.goto(INDEX_URL, { waitUntil: 'load' });
			await searchInDataGrid(adminPage, name);

			await expect(adminPage.getByText(name, { exact: true }).first()).toBeVisible();
			await expect(adminPage.locator('span[title="Edit"]')).toHaveCount(1);
			await expect(adminPage.locator('span[title="Delete"]')).toHaveCount(0);
		}
	});

	test('creates a custom association type with a field-builder field, persists it across reload, and deletes it', async ({ adminPage }) => {
		await ensureAssociationTypeAbsent(adminPage, 'bundle_kit');

		await test.step('create bundle_kit with a required quantity field', async () => {
			await adminPage.goto(INDEX_URL, { waitUntil: 'load' });
			await adminPage.getByRole('link', { name: 'Create Association Type' }).click();
			await adminPage.waitForLoadState('load');

			await adminPage.getByRole('textbox', { name: 'Code' }).fill('bundle_kit');

			// Locale name inputs have no accessible label (matches the pattern
			// used for category fields); fill every active locale so the
			// per-locale `required` name rule passes regardless of how many
			// locales are active.
			const nameInputs = adminPage.locator('input[name$="\\[name\\]"]');
			const localeCount = await nameInputs.count();

			for (let i = 0; i < localeCount; i++) {
				await nameInputs.nth(i).fill('Bundle / Kit');
			}

			await addAssociationTypeField(adminPage, {
				code: 'quantity',
				type: 'Text',
				validation: 'Number',
				required: true,
				section: 'General Section',
			});

			await saveViaUnsavedChangesBar(adminPage, /Association Type Created Successfully/i);
		});

		await test.step('quantity field persists after reloading the edit page', async () => {
			await adminPage.goto(INDEX_URL, { waitUntil: 'load' });
			await searchInDataGrid(adminPage, 'bundle_kit');

			const row = adminPage.locator('div', { hasText: 'bundle_kit' }).first();
			await row.locator('span[title="Edit"]').first().click();
			await expect(adminPage).toHaveURL(/\/admin\/catalog\/association-types\/edit\//);

			await expect(adminPage.getByText('quantity', { exact: true }).first()).toBeVisible();

			// Full server round-trip (not just client-side state) — the edit
			// controller re-hydrates `fields` from `association_type_fields`.
			await adminPage.reload({ waitUntil: 'load' });
			await expect(adminPage.getByText('quantity', { exact: true }).first()).toBeVisible();
		});

		await test.step('delete the custom association type', async () => {
			await adminPage.goto(INDEX_URL, { waitUntil: 'load' });
			await searchInDataGrid(adminPage, 'bundle_kit');

			const row = adminPage.locator('div', { hasText: 'bundle_kit' }).first();
			await row.locator('span[title="Delete"]').first().click();
			await adminPage.getByRole('button', { name: 'Delete' }).click();

			await expect(adminPage.locator('#app').getByText(/Association Type Deleted Successfully/i)).toBeVisible();
		});
	});

});
