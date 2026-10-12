/**
 * Category-field payload generator.
 *
 * Category fields are typed columns attached to categories. Common types:
 *   text, textarea, select, multiselect, boolean, date, file, image
 */
'use strict';

const { uniqueCode, SEED } = require('../utils/api');

function build(overrides = {}) {
  const code = overrides.code || uniqueCode('catf');
  return {
    code,
    type: overrides.type || 'text',
    status: overrides.status !== undefined ? overrides.status : 1,
    position: overrides.position ?? 0,
    is_required: overrides.is_required ?? false,
    is_unique: overrides.is_unique ?? false,
    value_per_locale: overrides.value_per_locale ?? false,
    validation: overrides.validation || null,
    regex_pattern: overrides.regex_pattern || null,
    section: overrides.section || 'general',
    enable_wysiwyg: overrides.enable_wysiwyg ?? false,
    labels: overrides.labels || { [SEED.defaultLocale]: `Test Field ${code}` },
  };
}

function buildSelect(overrides = {}) {
  return build({ type: 'select', ...overrides });
}

function buildBoolean(overrides = {}) {
  return build({ type: 'boolean', ...overrides });
}

function buildMissingType(overrides = {}) {
  const p = build(overrides);
  delete p.type;
  return p;
}

/**
 * UnoPim enforces these fields as immutable on PUT:
 *   code, type, value_per_locale, is_unique
 * `buildUpdate` strips the full set so update payloads don't trip the
 * "fields cannot be modified" 422 from the framework.
 */
function buildUpdate(overrides = {}) {
  const p = build(overrides);
  for (const k of ['code', 'type', 'value_per_locale', 'is_unique']) {
    delete p[k];
  }
  return p;
}

module.exports = { build, buildSelect, buildBoolean, buildMissingType, buildUpdate };
