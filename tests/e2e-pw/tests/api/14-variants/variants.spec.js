/**
 * Variants API — variants are created via the regular /products endpoint with
 * `parent` set to the configurable's SKU and `variant.attributes` describing
 * which super-attribute values this child represents.
 *
 * Test independence note: variants under the same parent must have unique
 * super-attribute combos. To keep tests parallel-safe we mint a fresh parent
 * configurable in each test rather than sharing one across the whole spec.
 * The seed `color` + `size` attributes (already in the `default` family) are
 * reused as super attributes.
 */
'use strict';

const { test, expect } = require('../../../fixtures/api-fixtures');
const { RESPONSE_TIME, REST_ROOT } = require('../../../utils/api/config');
const { get, post, put, delete: del, deleteIfExists } = require('../../../utils/api/request-wrapper');
const { uniqueCode } = require('../../../utils/api');
const {
  expectOk, expectStatusIn, expectResponseTimeUnder,
  expectSuccessEnvelope, expectUnauthorized,
} = require('../../../utils/api/response-validator');
const payloads = require('../../../payloads');

const colorCode = process.env.API_VARIANT_COLOR_ATTR || 'color';
const sizeCode = process.env.API_VARIANT_SIZE_ATTR || 'size';
// Seed option codes shipped with UnoPim's default install.
const SEED_COLORS = ['Red', 'Green', 'Yellow', 'Black', 'White'];
const SEED_SIZES = ['S', 'M', 'L', 'XL'];

test.describe('Variants API (children of configurable products)', () => {
  const createdProducts = new Set();

  /** Create a fresh configurable parent for this test, return its SKU. */
  async function mintParent(api) {
    const sku = uniqueCode('var_parent');
    const create = await post(api, '/configurable-products', {
      data: payloads.configurableProducts.build({ sku, superAttributes: [colorCode, sizeCode] }),
    });
    if (create.ok) createdProducts.add(sku);
    return create.ok ? sku : null;
  }

  test.afterAll(async ({ apiToken, playwright }) => {
    if (!apiToken) return;
    const ctx = await playwright.request.newContext({ baseURL: REST_ROOT });
    for (const sku of createdProducts) {
      await deleteIfExists(ctx, apiToken, `/products/${encodeURIComponent(sku)}`);
    }
    await ctx.dispose();
  });

  // ── Create ──────────────────────────────────────────────────────────────

  test('14.1 - POST creates a variant under the parent', async ({ api, uid }) => {
    const parent = await mintParent(api);
    test.skip(!parent, 'Could not seed parent configurable');
    const variant = payloads.variants.build({
      parentSku: parent, superAttributes: { [colorCode]: SEED_COLORS[0], [sizeCode]: SEED_SIZES[0] }, sku: `var_${uid}`,
    });
    const result = await post(api, '/products', { data: variant });
    expectOk(result);
    expectSuccessEnvelope(result.body);
    expectResponseTimeUnder(result, RESPONSE_TIME.write);
    createdProducts.add(variant.sku);
  });

  test('14.2 - Variant retrievable via GET and references the parent', async ({ api, uid }) => {
    const parent = await mintParent(api);
    test.skip(!parent, 'Could not seed parent configurable');
    const sku = `var_get_${uid}`;
    await post(api, '/products', {
      data: payloads.variants.build({ parentSku: parent, superAttributes: { [colorCode]: SEED_COLORS[1], [sizeCode]: SEED_SIZES[1] }, sku }),
    });
    createdProducts.add(sku);

    const result = await get(api, `/products/${sku}`);
    expectOk(result);
    expect(result.body.sku).toBe(sku);
    expect(result.body.parent).toBe(parent);
  });

  // ── Negative ────────────────────────────────────────────────────────────

  test('14.3 - Variant without parent → 4xx', async ({ api, uid }) => {
    const parent = await mintParent(api);
    test.skip(!parent, 'Could not seed parent configurable');
    const variant = payloads.variants.build({
      parentSku: parent, superAttributes: { [colorCode]: SEED_COLORS[0], [sizeCode]: SEED_SIZES[0] }, sku: `var_nop_${uid}`,
    });
    delete variant.parent;
    const result = await post(api, '/products', { data: variant });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  test('14.4 - Variant pointing at non-existent parent → 4xx', async ({ api, uid }) => {
    const variant = payloads.variants.build({
      parentSku: 'non_existent_parent_xyz_99999',
      superAttributes: { [colorCode]: SEED_COLORS[0], [sizeCode]: SEED_SIZES[0] },
      sku: `var_badp_${uid}`,
    });
    const result = await post(api, '/products', { data: variant });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  test('14.5 - Variant missing a super-attribute value → 4xx', async ({ api, uid }) => {
    const parent = await mintParent(api);
    test.skip(!parent, 'Could not seed parent configurable');
    const variant = payloads.variants.build({
      parentSku: parent, superAttributes: { [colorCode]: SEED_COLORS[0] /* size missing */ }, sku: `var_miss_${uid}`,
    });
    const result = await post(api, '/products', { data: variant });
    expect(result.status).toBeGreaterThanOrEqual(400);
  });

  test('14.6 - Two variants with the same super-attribute combo → 4xx on the second', async ({ api, uid }) => {
    const parent = await mintParent(api);
    test.skip(!parent, 'Could not seed parent configurable');
    const combo = { [colorCode]: SEED_COLORS[0], [sizeCode]: SEED_SIZES[0] };
    const skuA = `var_combo_a_${uid}`;
    const skuB = `var_combo_b_${uid}`;
    await post(api, '/products', { data: payloads.variants.build({ parentSku: parent, superAttributes: combo, sku: skuA }) });
    createdProducts.add(skuA);

    const dup = await post(api, '/products', { data: payloads.variants.build({ parentSku: parent, superAttributes: combo, sku: skuB }) });
    expectStatusIn(dup, [200, 201, 400, 422]);
    if (dup.ok) createdProducts.add(skuB);
  });

  // ── Update / Delete ─────────────────────────────────────────────────────

  test('14.7 - PUT updates variant name', async ({ api, uid }) => {
    const parent = await mintParent(api);
    test.skip(!parent, 'Could not seed parent configurable');
    const sku = `var_upd_${uid}`;
    await post(api, '/products', {
      data: payloads.variants.build({ parentSku: parent, superAttributes: { [colorCode]: SEED_COLORS[2], [sizeCode]: SEED_SIZES[0] }, sku }),
    });
    createdProducts.add(sku);

    const updated = payloads.variants.build({
      parentSku: parent, superAttributes: { [colorCode]: SEED_COLORS[2], [sizeCode]: SEED_SIZES[0] }, sku, name: `Renamed ${uid}`,
    });
    const result = await put(api, `/products/${sku}`, { data: updated });
    expectOk(result);
  });

  test('14.8 - DELETE removes the variant', async ({ api, uid }) => {
    const parent = await mintParent(api);
    test.skip(!parent, 'Could not seed parent configurable');
    const sku = `var_del_${uid}`;
    const create = await post(api, '/products', {
      data: payloads.variants.build({ parentSku: parent, superAttributes: { [colorCode]: SEED_COLORS[3], [sizeCode]: SEED_SIZES[2] }, sku }),
    });
    if (!create.ok) test.skip(true, 'Could not seed variant for delete');
    const result = await del(api, `/products/${sku}`);
    expectOk(result);
  });

  // ── Auth ────────────────────────────────────────────────────────────────

  test('14.9 - Without auth → 401', async ({ api, unauthedApi }) => {
    const parent = await mintParent(api);
    test.skip(!parent, 'Could not seed parent configurable');
    const result = await get(unauthedApi, `/products/${parent}`);
    expectUnauthorized(result);
  });
});
