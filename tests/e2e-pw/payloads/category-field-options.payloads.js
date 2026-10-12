/**
 * Category-field option payloads.
 * POST/PUT bodies are an ARRAY of options.
 */
'use strict';

const { uniqueCode, SEED } = require('../utils/api');

function buildOne(overrides = {}) {
  const code = overrides.code || uniqueCode('opt');
  return {
    code,
    sort_order: overrides.sort_order ?? 1,
    labels: overrides.labels || {
      [SEED.defaultLocale]: overrides.label || `Option ${code}`,
    },
  };
}

function build(count = 2, overrides = {}) {
  const prefix = overrides.codePrefix || 'opt';
  return Array.from({ length: count }, (_, i) =>
    buildOne({ ...overrides, code: uniqueCode(`${prefix}_${i}`), sort_order: i + 1 }),
  );
}

module.exports = { build, buildOne };
