/**
 * Attribute-group payloads.
 */
'use strict';

const { uniqueCode, SEED } = require('../utils/api');

function build(overrides = {}) {
  const code = overrides.code || uniqueCode('grp');
  return {
    code,
    position: overrides.position ?? 1,
    labels: overrides.labels || { [SEED.defaultLocale]: `Group ${code}` },
  };
}

function buildMissingCode(overrides) {
  const p = build(overrides);
  delete p.code;
  return p;
}

module.exports = { build, buildMissingCode };
