import { request } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import helpers from './utils/category-field-helpers.js';

const STORAGE_PATH = path.resolve(process.env.PW_STATE_DIR || '.state', 'admin-auth.json');

/**
 * Delete the enabled category fields specs created through the UI. They are
 * removed only once every worker has finished, so no category form still open
 * in another worker posts a field that no longer exists.
 */
export default async function globalTeardown() {
  const file = helpers.DEFERRED_FIELDS_FILE;

  if (! fs.existsSync(file)) {
    return;
  }

  const ids = [...new Set(fs.readFileSync(file, 'utf8').split('\n').filter(Boolean))];

  fs.rmSync(file);

  if (ids.length === 0 || ! fs.existsSync(STORAGE_PATH)) {
    return;
  }

  const context = await request.newContext({
    baseURL: process.env.BASE_URL || 'http://127.0.0.1:8000',
    storageState: STORAGE_PATH,
  });

  try {
    await context.get('/admin/catalog/category-fields');

    const cookie = (await context.storageState()).cookies.find((c) => c.name === 'XSRF-TOKEN');
    const headers = { Accept: 'application/json', 'X-XSRF-TOKEN': cookie ? decodeURIComponent(cookie.value) : '' };

    for (const id of ids) {
      await context.delete(`/admin/catalog/category-fields/edit/${id}`, { headers }).catch(() => {});
    }
  } finally {
    await context.dispose();
  }
}
