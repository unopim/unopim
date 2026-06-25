/**
 * Product payload generator.
 *
 * The most important quirk: attribute values live under one of four buckets
 * in `values.*`, chosen by the attribute's flags:
 *   - both flags false           → values.common.{attr}
 *   - value_per_locale = true    → values.locale_specific.{locale}.{attr}
 *   - value_per_channel = true   → values.channel_specific.{channel}.{attr}
 *   - both flags true            → values.channel_locale_specific.{channel}.{locale}.{attr}
 *
 * `price` attributes are arrays of `{currency, amount}` objects everywhere.
 */
'use strict';

const { uniqueCode, SEED } = require('../utils/api');

/** Standard simple product, `name` populated for default channel + locale. */
function build(overrides = {}) {
  const sku = overrides.sku || uniqueCode('sku');
  return {
    sku,
    status: overrides.status ?? true,
    parent: overrides.parent ?? null,
    family: overrides.family || SEED.defaultFamily,
    type: overrides.type || 'simple',
    additional: overrides.additional || {},
    values: overrides.values || {
      common: { sku },
      categories: overrides.categories || [],
      channel_specific: {},
      locale_specific: {},
      channel_locale_specific: {
        [SEED.defaultChannel]: {
          [SEED.defaultLocale]: {
            name: overrides.name || `Test Product ${sku}`,
            ...(overrides.price !== undefined
              ? { price: priceArray(overrides.price) }
              : {}),
          },
        },
      },
    },
  };
}

function priceArray(amount, currency = SEED.defaultCurrency) {
  if (Array.isArray(amount)) return amount;
  return [{ currency, amount: String(amount) }];
}

function buildWithoutSku(overrides = {}) {
  const p = build(overrides);
  delete p.sku;
  // Also drop the duplicate sku inside values.common — otherwise UnoPim picks
  // up that copy and the create succeeds, defeating the negative test.
  if (p.values && p.values.common) delete p.values.common.sku;
  return p;
}

function buildWithoutFamily(overrides = {}) {
  const p = build(overrides);
  delete p.family;
  return p;
}

function buildVariant({ parentSku, superAttributes, sku, ...overrides }) {
  const variantSku = sku || uniqueCode('var');
  return {
    sku: variantSku,
    parent: parentSku,
    family: overrides.family || SEED.defaultFamily,
    type: 'simple',
    status: overrides.status ?? true,
    values: overrides.values || {
      common: { sku: variantSku },
      channel_locale_specific: {
        [SEED.defaultChannel]: {
          [SEED.defaultLocale]: {
            name: overrides.name || `Variant ${variantSku}`,
            ...(overrides.price !== undefined ? { price: priceArray(overrides.price) } : {}),
          },
        },
      },
    },
    variant: { attributes: superAttributes },
  };
}

module.exports = {
  build,
  buildVariant,
  buildWithoutSku,
  buildWithoutFamily,
  priceArray,
};
