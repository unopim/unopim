const { test, expect } = require('../../utils/fixtures');
const { searchInDataGrid, fillLocalizedField } = require('../../utils/helpers');

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

	// Either the toast appears OR the page redirects — fails only if BOTH
	// time out.
	await Promise.any([navPromise, toastPromise]);
}

function fieldModal(page) {
	return page.locator('form').filter({ has: page.getByRole('button', { name: 'Save Field' }) }).last();
}

async function addAssociationTypeField(page, { name, code, type, validation }) {
	await page.getByText('Add Field', { exact: true }).first().click();

	const modal = fieldModal(page);

	await modal.getByRole('textbox', { name: 'Name' }).fill(name);
	await modal.getByRole('textbox', { name: 'Code' }).fill(code);

	await modal.locator('.multiselect__tags').filter({ hasText: 'Select option' }).first().click();
	await page.getByRole('option', { name: type }).first().click();

	if (validation) {
		await modal.locator('.multiselect__tags').filter({ hasText: 'Select option' }).first().click();
		await page.getByRole('option', { name: validation }).first().click();
	}

	await modal.getByRole('button', { name: 'Save Field' }).click();

	await expect(page.getByText(code, { exact: true }).first()).toBeVisible();
}

async function configureAssociationTypeField(page, { required }) {
	await page.locator('span.icon-edit').first().click();

	const modal = fieldModal(page);

	if (required) {
		await modal.getByText('Is Required', { exact: true }).click();
	}

	await modal.getByRole('button', { name: 'Save Field' }).click();

	await expect(modal.getByRole('button', { name: 'Save Field' })).toBeHidden();
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
		await page.locator('.max-w-\\[400px\\]').getByRole('button', { name: 'Delete', exact: true }).click();
		await page.waitForLoadState('load');
		await page.waitForTimeout(500);
	}
}

test.describe('UnoPim Association Type Tests', () => {

	test('seeded default association types show and expose no delete control', async ({ adminPage }) => {
		// Searching/asserting by code rather than the translated label: the label
		// copy (e.g. "Related products" vs "Related Products") is demo-seed
		// wording and not a stable contract, but the code is.
		for (const code of ['related_products', 'up_sells', 'cross_sells']) {
			await adminPage.goto(INDEX_URL, { waitUntil: 'load' });
			await searchInDataGrid(adminPage, code);

			await expect(adminPage.getByText(code, { exact: true }).first()).toBeVisible();
			await expect(adminPage.locator('span[title="Edit"]')).toHaveCount(1);
			await expect(adminPage.locator('span[title="Delete"]')).toHaveCount(0);
		}
	});

	test('creates a custom association type with a field-builder field, persists it across reload, and deletes it', async ({ adminPage }) => {
		await ensureAssociationTypeAbsent(adminPage, 'bundle_kit');

		await test.step('create bundle_kit via the code-only modal', async () => {
			await adminPage.goto(INDEX_URL, { waitUntil: 'load' });

					await adminPage.getByRole('button', { name: 'Create Association Type' }).click();

			// Scope to the modal's own form (identified by its "Save Association
			// Type" button) so its "Code" field is unambiguous.
			const createModal = adminPage.locator('form')
				.filter({ has: adminPage.getByRole('button', { name: 'Save Association Type' }) })
				.last();

			await createModal.getByRole('textbox', { name: 'Enter Name' }).fill('Bundle / Kit');
			await createModal.getByRole('textbox', { name: 'Enter Code' }).fill('bundle_kit');
			await createModal.getByRole('button', { name: 'Save Association Type' }).click();

			await expect(adminPage).toHaveURL(/\/admin\/catalog\/association-types\/edit\//);
		});

		await test.step('configure labels and a required quantity field on the edit page', async () => {
			// The name field is the translatable-field component: its per-locale
			// inputs travel as hidden fields and only the active locale's editor
			// is visible. Filling it updates every locale's hidden value (seeded
			// from the create-time name), which satisfies the per-locale
			// `required` name rule.
			await fillLocalizedField(adminPage, 'Bundle / Kit');

			await addAssociationTypeField(adminPage, {
				name: 'Quantity',
				code: 'quantity',
				type: 'Text',
				validation: 'Number',
			});

			await configureAssociationTypeField(adminPage, {
				required: true,
			});

			await saveViaUnsavedChangesBar(adminPage, /Association Type Updated Successfully/i);
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

			await expect(adminPage.getByRole('columnheader', { name: 'Actions' }).first()).toBeVisible();

			await adminPage.locator('span.icon-edit').first().click();

			const modal = fieldModal(adminPage);

			await expect(modal.locator('input[name="is_required"]')).toBeChecked();
		});

		await test.step('the history tab lists the versions of the type and its fields', async () => {
			const editUrl = adminPage.url().split('?')[0];

			await adminPage.goto(editUrl + '?history=1', { waitUntil: 'load' });

			await expect(adminPage.getByRole('link', { name: /history/i }).first()).toBeVisible();
			await expect(adminPage.locator('main').getByText('Version', { exact: true }).first()).toBeVisible();
			await expect(adminPage.locator('main').getByText('1', { exact: true }).first()).toBeVisible();
		});

		await test.step('delete the custom association type', async () => {
			await adminPage.goto(INDEX_URL, { waitUntil: 'load' });
			await searchInDataGrid(adminPage, 'bundle_kit');

			const row = adminPage.locator('div', { hasText: 'bundle_kit' }).first();
			await row.locator('span[title="Delete"]').first().click();
			await adminPage.locator('.max-w-\\[400px\\]').getByRole('button', { name: 'Delete', exact: true }).click();

			await expect(adminPage.locator('#app').getByText(/Association Type Deleted Successfully/i)).toBeVisible();
		});
	});

});
