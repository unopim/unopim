/**
 * Configurable-product payloads.
 *
 * A configurable parent declares its `super_attributes` (codes of select-type
 * attributes the variants differ by). Each child variant supplies values for
 * those super attributes via `variant.attributes`.
 */
'use strict';

const { uniqueCode, SEED } = require('../utils/api');

function build({ sku, superAttributes = ['color', 'size'], ...overrides } = {}) {
  const parentSku = sku || uniqueCode('cfg');
  return {
    sku: parentSku,
    type: 'configurable',
    family: overrides.family || SEED.defaultFamily,
    status: overrides.status ?? true,
    super_attributes: superAttributes,
    values: overrides.values || {
      common: { sku: parentSku },
      channel_locale_specific: {
        [SEED.defaultChannel]: {
          [SEED.defaultLocale]: { name: overrides.name || `Configurable ${parentSku}` },
        },
      },
    },
    ...(overrides.variants ? { variants: overrides.variants } : {}),
  };
}

module.exports = { build };
