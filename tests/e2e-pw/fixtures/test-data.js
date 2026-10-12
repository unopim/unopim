/**
 * Static registry of well-known codes that come with a fresh UnoPim install.
 *
 * Override any of these via env vars when running against a customised
 * environment. The codes here are referenced by negative tests
 * (e.g. "look up a known locale") and by payload defaults.
 */
'use strict';

const { SEED } = require('../utils/api/config');

module.exports = {
  // Read-only seed data — every fresh UnoPim install ships with these.
  seedLocale: SEED.defaultLocale,           // e.g. en_US
  seedSecondaryLocale: SEED.secondaryLocale, // e.g. fr_FR
  seedCurrency: SEED.defaultCurrency,       // e.g. USD
  seedChannel: SEED.defaultChannel,         // e.g. default
  rootCategory: SEED.rootCategory,          // e.g. root
  seedFamily: SEED.defaultFamily,           // e.g. default

  // Codes guaranteed not to exist — used by 404 tests.
  nonExistent: {
    locale: 'zz_ZZ',
    currency: 'ZZZ',
    channel: 'this_channel_does_not_exist_xyz',
    category: 'non_existent_category_xyz_999',
    attribute: 'non_existent_attribute_xyz_999',
    attributeGroup: 'non_existent_group_xyz_999',
    family: 'non_existent_family_xyz_999',
    sku: 'NON_EXISTENT_SKU_XYZ_999',
  },

  // Default seed attributes that ship with every UnoPim install.
  builtInAttributes: ['sku', 'name'],
};
