// utils/category-field-helpers.js — keep category-field specs from breaking category forms in other workers.
//
// Every enabled category field renders on the category create/edit form, and
// the form posts all of them back. Deleting an enabled field while another
// worker has a category form open makes that worker's save fail with
// "Category Field with code … could not be found". So specs never delete an
// enabled field mid-run: fields a test deletes are seeded disabled, and
// enabled fields created through the UI are removed by global teardown.

const fs = require('fs');
const path = require('path');

const DEFERRED_FIELDS_FILE = path.resolve(
  __dirname,
  '..',
  process.env.PW_STATE_DIR || '.state',
  'deferred-category-fields.txt',
);

/**
 * Read the XSRF token Laravel accepts in the X-XSRF-TOKEN header.
 * @param {import('@playwright/test').BrowserContext} context
 * @returns {Promise<string>}
 */
async function xsrfToken(context) {
  const cookie = (await context.cookies()).find((c) => c.name === 'XSRF-TOKEN');

  return cookie ? decodeURIComponent(cookie.value) : '';
}

/**
 * Create a disabled category field, which no category form renders, so a test
 * may delete it at any time. Returns the new field's id.
 * @param {import('@playwright/test').Page} page
 * @param {string} code
 * @param {string} [type='text']
 * @returns {Promise<number>}
 */
async function seedDisabledCategoryField(page, code, type = 'text') {
  if (! (await xsrfToken(page.context()))) {
    await page.goto('/admin/catalog/category-fields', { waitUntil: 'load' });
  }

  const response = await page.request.post('/admin/catalog/category-fields/create', {
    headers: { 'X-XSRF-TOKEN': await xsrfToken(page.context()) },
    form: { code, type, status: '0' },
  });

  const match = response.url().match(/category-fields\/edit\/(\d+)/);

  if (! match) {
    throw new Error(`Seeding category field "${code}" failed: HTTP ${response.status()} at ${response.url()}`);
  }

  return Number(match[1]);
}

/**
 * Queue the category field whose edit page is open for deletion after the run.
 * @param {import('@playwright/test').Page} page
 */
function deferCategoryFieldCleanup(page) {
  const match = page.url().match(/category-fields\/edit\/(\d+)/);

  if (match) {
    fs.mkdirSync(path.dirname(DEFERRED_FIELDS_FILE), { recursive: true });
    fs.appendFileSync(DEFERRED_FIELDS_FILE, `${match[1]}\n`);
  }
}

module.exports = {
  DEFERRED_FIELDS_FILE,
  xsrfToken,
  seedDisabledCategoryField,
  deferCategoryFieldCleanup,
};
