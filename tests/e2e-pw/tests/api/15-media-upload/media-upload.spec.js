/**
 * Media Upload API.
 *
 * Two endpoints — one for products, one for categories. Both are multipart
 * uploads with field `file`. After upload, the returned `filePath` must be
 * patched back onto the resource to actually attach the media; we exercise
 * the upload contract here and verify the PATCH happy-path in 15.5/15.6.
 */
'use strict';

const fs = require('fs');
const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME, REST_ROOT, MEDIA_FIXTURES } = require('../../../utils/api/config');
const { post, patch, deleteIfExists } = require('../../../utils/api/request-wrapper');
const { uniqueCode } = require('../../../utils/api');
const {
  expectOk, expectStatusIn, expectResponseTimeUnder,
  expectMatchesSchema, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const schemas = require('../../../schemas');
const payloads = require('../../../payloads');
const testData = require('../../../fixtures/test-data');

test.describe('Media Upload API', () => {
  const createdProducts = new Set();
  const createdCategories = new Set();
  const createdCategoryFields = new Set();
  let productSku;
  let categoryCode;

  test.beforeAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({
      baseURL: REST_ROOT,
      extraHTTPHeaders: { Authorization: `Bearer ${apiToken}`, Accept: 'application/json', 'Content-Type': 'application/json' },
    });
    productSku = uniqueCode('media_sku');
    const p = await post(ctx, '/products', { data: payloads.products.build({ sku: productSku }) });
    if (p.ok) createdProducts.add(productSku); else productSku = null;

    categoryCode = uniqueCode('media_cat');
    const c = await post(ctx, '/categories', { data: payloads.categories.build({ code: categoryCode }) });
    if (c.ok) createdCategories.add(categoryCode); else categoryCode = null;

    await ctx.dispose();
  });

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const sku of createdProducts) await deleteIfExists(ctx, apiToken, `/products/${encodeURIComponent(sku)}`);
    for (const code of createdCategories) await deleteIfExists(ctx, apiToken, `/categories/${encodeURIComponent(code)}`);
    for (const code of createdCategoryFields) await deleteIfExists(ctx, apiToken, `/category-fields/${encodeURIComponent(code)}`);
    await ctx.dispose();
  });

  // ── Product media ───────────────────────────────────────────────────────

  test('15.1 - POST /media-files/product uploads an image', async ({ api }) => {
    if (!productSku) test.skip(true, 'Seed product not created');
    const payload = payloads.media.buildProductUpload({ sku: productSku });
    const result = await post(api, '/media-files/product', {
      multipart: {
        sku: payload.sku,
        attribute: payload.attribute,
        file: payload.file,
      },
    });
    expectOk(result);
    expectMatchesSchema(schemas.mediaUpload, result.body, 'mediaUpload');
    expectResponseTimeUnder(result, RESPONSE_TIME.upload);
    expect(result.body.data && result.body.data.filePath).toBeTruthy();
  });

  test('15.2 - Product upload without SKU → 4xx', async ({ api }) => {
    const file = payloads.media.readAsMultipartFile(MEDIA_FIXTURES.jpegSmall);
    const result = await post(api, '/media-files/product', {
      multipart: { attribute: 'image', file },
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  test('15.3 - Product upload with non-existent SKU → 4xx', async ({ api }) => {
    const file = payloads.media.readAsMultipartFile(MEDIA_FIXTURES.jpegSmall);
    const result = await post(api, '/media-files/product', {
      multipart: { sku: testData.nonExistent.sku, attribute: 'image', file },
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  test('15.4 - Product upload missing `file` field → 4xx', async ({ api }) => {
    if (!productSku) test.skip(true, 'Seed product not created');
    const result = await post(api, '/media-files/product', {
      multipart: { sku: productSku, attribute: 'image' },
    });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  test('15.5 - filePath returned by upload can be PATCHed onto the product', async ({ api }) => {
    if (!productSku) test.skip(true, 'Seed product not created');
    const payload = payloads.media.buildProductUpload({ sku: productSku });
    const upload = await post(api, '/media-files/product', {
      multipart: { sku: payload.sku, attribute: payload.attribute, file: payload.file },
    });
    expectOk(upload);
    const filePath = upload.body.data.filePath;

    // Attach as a common (non-localized) image value. The exact bucket depends on
    // the attribute's flags — image is typically `values.common.image`.
    const patchBody = { values: { common: { image: filePath } } };
    const result = await patch(api, `/products/${productSku}`, { data: patchBody });
    expectStatusIn(result, [200, 201]);
  });

  // ── Category media ──────────────────────────────────────────────────────

  test('15.6 - POST /media-files/category uploads a file', async ({ api, apiToken, playwright }) => {
    if (!categoryCode) test.skip(true, 'Seed category not created');

    // Seed an `image`-type category-field — `file`-type fields default to
    // PDF-only validation, which would reject the JPEG fixture.
    const fieldCode = uniqueCode('cf_media');
    const ctx = await playwright.request.newContext({
      baseURL: REST_ROOT,
      extraHTTPHeaders: { Authorization: `Bearer ${apiToken}`, Accept: 'application/json', 'Content-Type': 'application/json' },
    });
    const cf = await post(ctx, '/category-fields', {
      data: payloads.categoryFields.build({ code: fieldCode, type: 'image' }),
    });
    if (cf.ok) createdCategoryFields.add(fieldCode);
    await ctx.dispose();

    const payload = payloads.media.buildCategoryUpload({ code: categoryCode, categoryField: fieldCode });
    const result = await post(api, '/media-files/category', {
      multipart: { code: payload.code, category_field: payload.category_field, file: payload.file },
    });
    expectOk(result);
    expectMatchesSchema(schemas.mediaUpload, result.body, 'mediaUpload');
    expectResponseTimeUnder(result, RESPONSE_TIME.upload);
  });

  // ── Auth ────────────────────────────────────────────────────────────────

  test('15.7 - Upload without auth → 401', async ({ playwright }) => {
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    const file = payloads.media.readAsMultipartFile(MEDIA_FIXTURES.jpegSmall);
    const result = await post(ctx, '/media-files/product', {
      multipart: { sku: 'whatever', attribute: 'image', file },
    });
    expectUnauthorized(result);
    await ctx.dispose();
  });

  test('15.8 - Fixture image files exist on disk', () => {
    for (const f of Object.values(MEDIA_FIXTURES)) {
      expect(fs.existsSync(f), `${f} should exist`).toBe(true);
    }
  });
});
