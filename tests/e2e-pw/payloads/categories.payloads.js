/**
 * Category payload generator.
 *
 * Categories carry locale-specific labels under
 *   additional_data.locale_specific.{locale}.name
 * because their `name` attribute is a locale-scoped category field.
 */
'use strict';

const { uniqueCode, SEED } = require('../utils/api');

/** Build a complete create payload. */
function build(overrides = {}) {
  const code = overrides.code || uniqueCode('cat');
  return {
    code,
    parent: overrides.parent !== undefined ? overrides.parent : SEED.rootCategory,
    additional_data: overrides.additional_data || {
      locale_specific: {
        [SEED.defaultLocale]: {
          name: overrides.name || `Test Category ${code}`,
          description: overrides.description || `Auto-generated category for ${code}`,
        },
      },
    },
  };
}

/** Minimal payload missing `parent` — used by negative tests. */
function buildMissingParent(overrides = {}) {
  const { parent, ...rest } = build(overrides);
  return rest;
}

/** Missing required `code` field. */
function buildMissingCode(overrides = {}) {
  const payload = build(overrides);
  delete payload.code;
  return payload;
}

/** Update payload — re-uses build() but lets caller override the locale name. */
function buildUpdate(code, overrides = {}) {
  return build({ code, ...overrides });
}

module.exports = { build, buildMissingParent, buildMissingCode, buildUpdate };
