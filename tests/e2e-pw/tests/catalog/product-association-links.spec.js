const { test, expect } = require('../../utils/fixtures');
const { navigateTo, searchInDataGrid } = require('../../utils/helpers');

/**
 * Product edit "Links" panel — Plan 3 slice (rich association links).
 *
 * Precondition (created idempotently at the top of the single test below, via
 * the Plan-1 admin UI — same flow `association-types.spec.js` uses): a custom
 * association type `bundle_kit` ("Bundle / Kit") with a REQUIRED, numeric-
 * validated `quantity` custom field.
 *
 * Covers, against the real browser (Vue-mounted `v-product-links` /
 * `v-product-search` components):
 *  (a) `bundle_kit` renders as a dynamic association-type section on a
 *      product's Links panel — proves Task 4/5's per-type dynamic rendering,
 *      not just the 3 legacy hardcoded sections (Related/Up-sell/Cross-sell).
 *  (b) a related product can be added under `bundle_kit` via the
 *      product-search drawer, and its `quantity` custom field can be filled
 *      and saved — proves Task 3/5's unified `associations[...]` payload +
 *      per-link field editor round-trips through the real update route.
 *  (c) reloading the edit page shows the link + `quantity = 2` still there —
 *      persistence, not just client-side Vue state.
 *  (d) an UNRELATED save (toggling the product's Status switch, never
 *      touching the Links panel) still preserves `quantity = 2` after a
 *      reload — the Plan 3 Task 1 preservation guarantee: an ordinary
 *      product save must never silently wipe a link's `additional_data`.
 *
 * Blades: packages/Webkul/Admin/src/Resources/views/catalog/products/edit/links.blade.php
 *         packages/Webkul/Admin/src/Resources/views/components/associations/link-fields.blade.php
 *         packages/Webkul/Admin/src/Resources/views/components/products/search.blade.php
 *         packages/Webkul/Admin/src/Resources/views/catalog/associations/types/{index,create}.blade.php
 *         packages/Webkul/Admin/src/Resources/views/components/associations/field-builder.blade.php
 *
 * Selector notes (mapped to the ACTUAL Task-5 markup, not guessed):
 *  - Each association type's header ("Add" button + type name) is rendered
 *    as `<div class="flex gap-5 justify-between items-center">` in
 *    `links.blade.php` (one per type) — used below to scope the `bundle_kit`
 *    section's own "Add" trigger (the page has one such trigger per type, so
 *    an unscoped `getByText('Add')` would be ambiguous).
 *  - The product-search drawer's result rows are
 *    `<div class="flex gap-2.5 justify-between px-4 py-6 border-b ...">`
 *    (`components/products/search.blade.php`) — distinct from a *link* row's
 *    own `<div class="flex gap-2.5 justify-between p-4 border-b ...">`
 *    (`px-4 py-6` vs `p-4`), so `.px-4.py-6` scopes to the drawer's rows only.
 *  - Each per-link custom field's `name` is Vue-bound
 *    (`assocFieldName('bundle_kit', index, field)` in `links.blade.php`) to
 *    `associations[bundle_kit][<index>][additional_data][common][quantity]`
 *    — resolved into a real DOM attribute at runtime, so a plain
 *    `input[name="..."]` CSS selector matches it directly.
 */

const ASSOCIATION_TYPES_INDEX_URL = '/admin/catalog/association-types';
const BUNDLE_KIT_CODE = 'bundle_kit';
const BUNDLE_KIT_NAME = 'Bundle / Kit';
const QUANTITY_INPUT_SELECTOR = 'input[name="associations[bundle_kit][0][additional_data][common][quantity]"]';

/**
 * Every `x-admin::form` on this app (product edit and association-type
 * edit/create alike) defaults to `trackDirty + hideSaveWhenTracked`: once the
 * page/form mounts, `v-unsaved-changes` physically removes the form's own
 * named submit button from the DOM (see
 * `packages/Webkul/Admin/src/Resources/views/components/form/unsaved-changes.blade.php`),
 * leaving the floating "You have unsaved changes" bar's generic "Save
 * changes" button as the only way to submit. Mirrors
 * `association-types.spec.js`'s helper of the same name.
 */
async function saveViaUnsavedChangesBar(page, toastPattern) {
	const currentUrl = page.url();
	const regex = toastPattern instanceof RegExp ? toastPattern : new RegExp(toastPattern, 'i');

	const navPromise = page.waitForURL((url) => url.toString() !== currentUrl, { timeout: 20000 });
	const toastPromise = page.locator('#app').getByText(regex).first().waitFor({ state: 'visible', timeout: 20000 });

	await page.getByRole('button', { name: 'Save changes' }).click();

	// Either the toast appears OR the page redirects — fails only if BOTH time out.
	await Promise.any([navPromise, toastPromise]);
}

/**
 * Opens the field-builder's "Add Field" modal, fills it in, and saves it.
 * Verbatim copy of `association-types.spec.js`'s helper of the same name
 * (kept local so this spec file has no cross-file dependency).
 */
async function addAssociationTypeField(page, { code, type, validation, required, section }) {
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

	await modal.locator('.multiselect__tags').filter({ hasText: 'Select option' }).first().click();
	await page.getByRole('option', { name: section }).first().click();

	await modal.getByRole('button', { name: 'Save Field' }).click();

	await expect(page.getByText(code, { exact: true }).first()).toBeVisible();
}

/**
 * Idempotent precondition: ensures `bundle_kit` (with a required, numeric
 * `quantity` field) exists, creating it via the admin UI only if it isn't
 * already there. Never deletes it — `association-types.spec.js` owns that
 * type's full CRUD lifecycle test independently; this spec only needs the
 * type to exist as a fixture.
 */
async function ensureBundleKitAssociationTypeExists(page) {
	await page.goto(ASSOCIATION_TYPES_INDEX_URL, { waitUntil: 'load' });

	// NOTE: deliberately not using `searchInDataGrid()` + a plain
	// `.isVisible({ timeout })` check here (the pattern `association-
	// types.spec.js`'s own `ensureAssociationTypeAbsent()` uses) --
	// `Locator.isVisible()` probes the CURRENT DOM state once and returns
	// immediately (confirmed empirically: it resolved in ~4ms against a
	// freshly-searched grid), it does NOT poll/wait the way
	// `expect(...).toBeVisible()` does. The DataGrid's search-filter
	// round-trip on this box measurably lags the fixed 500ms
	// `searchInDataGrid()` already sleeps (observed 1-2s to actually filter
	// down to one row), so an immediate `isVisible()` right after it
	// reliably raced to `false` even when `bundle_kit` already existed,
	// causing a duplicate "Create Association Type" attempt that failed
	// server-side with "The code has already been taken." Skipping the
	// search entirely and polling for the code's plain-text cell (via a
	// real `expect().toBeVisible()`, which DOES wait) on the always-first
	// page of the (currently tiny, <=10-row) unfiltered grid sidesteps the
	// race altogether.
	let alreadyExists = true;

	try {
		await expect(page.getByText(BUNDLE_KIT_CODE, { exact: true }).first()).toBeVisible({ timeout: 8000 });
	} catch {
		alreadyExists = false;
	}

	if (alreadyExists) {
		return;
	}

	await page.getByRole('link', { name: 'Create Association Type' }).click();
	await page.waitForLoadState('load');

	await page.getByRole('textbox', { name: 'Code' }).fill(BUNDLE_KIT_CODE);

	// Locale name inputs have no accessible label; fill every active locale.
	const nameInputs = page.locator('input[name$="\\[name\\]"]');
	const localeCount = await nameInputs.count();

	for (let i = 0; i < localeCount; i++) {
		await nameInputs.nth(i).fill(BUNDLE_KIT_NAME);
	}

	await addAssociationTypeField(page, {
		code: 'quantity',
		type: 'Text',
		validation: 'Number',
		required: true,
		section: 'General Section',
	});

	await saveViaUnsavedChangesBar(page, /Association Type Created Successfully/i);
}

/**
 * Select a value from a Vue-multiselect dropdown by field name. Verbatim
 * copy of `01-catalog/products.spec.js`'s helper of the same name.
 */
async function selectMultiselect(page, fieldName, optionLabel) {
	const wrapper = page.locator(`input[name="${fieldName}"]`).locator('..');
	await wrapper.locator('.multiselect__tags').click();
	await wrapper.locator('.multiselect__content-wrapper').first().waitFor({ state: 'visible', timeout: 5000 });

	if (optionLabel) {
		await page.getByRole('option', { name: optionLabel }).first().click();
	} else {
		await wrapper
			.locator('.multiselect__element:not(.multiselect__element--disabled) .multiselect__option:not(.multiselect__option--disabled)')
			.first()
			.click();
	}

	await page.keyboard.press('Escape');
}

/**
 * Creates a simple product and waits for the redirect to its edit page.
 * Returns the sku (the caller already knows it) purely for readability.
 */
async function createSimpleProduct(page, sku) {
	await navigateTo(page, 'products');
	await page.getByRole('button', { name: 'Create Product' }).click();
	await page.waitForLoadState('networkidle');
	await selectMultiselect(page, 'type', 'Simple');
	await selectMultiselect(page, 'attribute_family_id');
	await page.locator('input[name="sku"]').fill(sku);
	await page.getByRole('button', { name: 'Save Product' }).click();
	await page.waitForURL(/\/admin\/catalog\/products\/edit\//, { waitUntil: 'domcontentloaded', timeout: 30000 });
	await page.waitForLoadState('networkidle').catch(() => {});

	return sku;
}

/**
 * Fill a TinyMCE editor by its textarea ID. Verbatim copy of
 * `01-catalog/products.spec.js`'s helper of the same name.
 */
async function fillTinyMCE(page, editorId, text) {
	const iframe = page.locator(`#${editorId}_ifr`);
	await iframe.scrollIntoViewIfNeeded();
	await iframe.waitFor({ state: 'visible', timeout: 10000 });
	const frame = page.frameLocator(`#${editorId}_ifr`);
	await frame.locator('body[contenteditable="true"]').waitFor({ state: 'visible', timeout: 10000 });
	await frame.locator('body').click();
	await page.keyboard.type(text);
	await page.evaluate((id) => {
		const editor = tinymce.get(id);
		if (editor) {
			editor.fire('change');
			editor.save();
		}
	}, editorId);
}

/**
 * The `default` family's General group requires `name`, `url_key`,
 * `short_description`, `description`, and `price` (confirmed empirically: a
 * fresh product created with only sku/type/family, per `createSimpleProduct`
 * above, saves fine on CREATE, but the EDIT page's client-side VeeValidate
 * blocks the "Save changes" submit -- with zero network request and zero
 * console error, just inline "The X field is required" text -- until these
 * are filled). Filling them here is a product-data precondition for this
 * spec's later saves, not part of the associations feature under test.
 */
async function fillRequiredProductAttributes(page, { name, urlKey, price }) {
	await page.locator('#name').fill(name);
	await page.locator('#url_key').fill(urlKey);
	await fillTinyMCE(page, 'short_description', 'E2E test short description.');
	await fillTinyMCE(page, 'description', 'E2E test description.');
	await page.locator('#price').fill(price);
}

/**
 * Best-effort cleanup: deletes a product by SKU. Silently no-ops if it's
 * already gone (e.g. a previous, interrupted run already removed it).
 */
async function deleteProductBySku(page, sku) {
	await navigateTo(page, 'products');
	await searchInDataGrid(page, sku);

	const deleteIcon = page.locator('span[title="Delete"]').first();
	const visible = await deleteIcon.isVisible({ timeout: 3000 }).catch(() => false);

	if (!visible) {
		return;
	}

	await deleteIcon.click();
	await page.getByRole('button', { name: 'Delete' }).click();
	await page.waitForLoadState('networkidle').catch(() => {});
}

test.describe('Product Edit — rich association Links (bundle_kit custom type)', () => {

	test('adds a bundle_kit link with a quantity custom field, persists it across reload, and preserves it through an unrelated product save', async ({ adminPage, uid }) => {
		await ensureBundleKitAssociationTypeExists(adminPage);

		const mainSku = `assoc_link_main_${uid}`;
		const relatedSku = `assoc_link_related_${uid}`;

		// `.flex.gap-5.justify-between.items-center` is the exact per-type
		// header wrapper in `links.blade.php` (name + its own "Add" trigger).
		const bundleKitHeader = adminPage
			.locator('div.flex.gap-5.justify-between.items-center')
			.filter({ hasText: BUNDLE_KIT_NAME });

		try {
			await test.step('create the related product and the main product under test', async () => {
				await createSimpleProduct(adminPage, relatedSku);
				await createSimpleProduct(adminPage, mainSku);
				// adminPage is now on the main product's edit page.

				// Fill the main product's other required attributes up front
				// so the later "Save changes" clicks (adding the bundle_kit
				// link, and the unrelated Status toggle) aren't blocked by
				// client-side validation unrelated to associations.
				await fillRequiredProductAttributes(adminPage, {
					name: `Assoc Link Main ${uid}`,
					urlKey: mainSku,
					price: '10',
				});
			});

			await test.step('bundle_kit appears as a dynamic association type on the Links panel', async () => {
				await expect(bundleKitHeader).toBeVisible();
			});

			await test.step('add the related product under bundle_kit via the search drawer and fill quantity=2', async () => {
				await bundleKitHeader.getByText('Add', { exact: true }).click();

				await expect(adminPage.getByText('Select Products', { exact: true })).toBeVisible();

				await adminPage.getByPlaceholder('Search by sku').fill(relatedSku);
				// v-debounce dispatches a synthetic 'change' 500ms after 'input'
				// before the axios search fires; give it headroom.
				await adminPage.waitForTimeout(700);

				// Drawer result rows: `.flex.justify-between.px-4.py-6.border-b`
				// (distinct from a Links-panel link row's `.p-4`).
				const resultRow = adminPage
					.locator('div.flex.justify-between.px-4.py-6.border-b')
					.filter({ hasText: relatedSku });

				await expect(resultRow).toBeVisible({ timeout: 15000 });
				await resultRow.locator('input[type="checkbox"]').check({ force: true });

				await adminPage.getByText('Add Selected Product', { exact: true }).click();

				const quantityInput = adminPage.locator(QUANTITY_INPUT_SELECTOR);
				await expect(quantityInput).toBeVisible();
				await quantityInput.fill('2');

				await saveViaUnsavedChangesBar(adminPage, /Product updated successfully/i);
			});

			await test.step('reload — the bundle_kit link and quantity=2 persist', async () => {
				await adminPage.reload({ waitUntil: 'load' });
				await expect(bundleKitHeader).toBeVisible();
				await expect(adminPage.locator(QUANTITY_INPUT_SELECTOR)).toHaveValue('2');
			});

			await test.step('an unrelated save (toggling Status, never touching Links) still preserves quantity=2', async () => {
				await adminPage.locator('input[name="status"][type="checkbox"]').click({ force: true });
				await saveViaUnsavedChangesBar(adminPage, /Product updated successfully/i);

				await adminPage.reload({ waitUntil: 'load' });
				await expect(bundleKitHeader).toBeVisible();
				await expect(adminPage.locator(QUANTITY_INPUT_SELECTOR)).toHaveValue('2');
			});
		} finally {
			await test.step('cleanup: delete both test products', async () => {
				await deleteProductBySku(adminPage, mainSku);
				await deleteProductBySku(adminPage, relatedSku);
			});
		}
	});

});
