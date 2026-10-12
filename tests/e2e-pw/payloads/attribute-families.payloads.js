/**
 * Attribute-family payloads.
 *
 * A family bundles `attribute_groups[]`, each containing `custom_attributes[]`
 * (refs to existing attribute codes). The default `sku` + `name` attributes
 * always exist on a fresh install and are safe to reference.
 */
'use strict';

const { uniqueCode, SEED } = require('../utils/api');

function build(overrides = {}) {
  const code = overrides.code || uniqueCode('fam');
  return {
    code,
    labels: overrides.labels || { [SEED.defaultLocale]: `Family ${code}` },
    attribute_groups: overrides.attribute_groups || [
      {
        code: 'general',
        position: 1,
        custom_attributes: overrides.custom_attributes || [
          { code: 'sku', position: 1 },
          { code: 'name', position: 2 },
        ],
      },
    ],
  };
}

function buildWithGroups(groups, overrides = {}) {
  return build({ ...overrides, attribute_groups: groups });
}

module.exports = { build, buildWithGroups };
